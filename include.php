<?php

function rtf_isPlainText($s)
{
	$arrfailAt = array("*", "fonttbl", "colortbl", "datastore", "themedata");
	for ($i = 0; $i < count($arrfailAt); $i++)
		if (!empty($s[$arrfailAt[$i]])) return false;
	return true;
}

/**
 * This function has been found on Stack Overflow
 * I've made couple modifications for table parsing though
 */
function rtf2txt($filename)
{
	// Read the data from the input file.
	$text = file_get_contents($filename);

	$trStart = "\\\\trowd.*\n";
	$cellDef = ".*\\\\cellx\d+\n";
	$cell = "\n.*\n(.*)\\\\intbl\\\\cell\n";
	$cellLineBreak = "\\\\par\n\\\\pard\\\\plain\\\\s0\\\\f0\\\\fs24\\\\q\w\\\\fi0\\\\li0\\\\b0\\\\i0\\\\nosupersub\\\\ul0\\\\strike0\\\\intbl\n";
	$trEnd = "\n{$trStart}{$cellDef}{$cellDef}{$cellDef}\\\\row\n";

	// метка конца таблиц
	$text = str_replace("\\row\n\\pard","\\row\n#TABLE_END#\n\\pard",$text);
	// убираем переносы внутри ячеек
	$text = preg_replace("/{$cellLineBreak}/"," ",$text);
	// картинки в ячейках
	$text = preg_replace("/\{\\\\~\}\{[.\s\S]*?\}\}\n/","*IMAGE*",$text);
	// примечания со ссылками
	$text = preg_replace('/\\{\\\\field\\{\\\\\\*\\\\fldinst\\{HYPERLINK \\\\\\\\l Par\d+  \\\\\\\\o "[^\\}]+\\}\\}\\{\\\\fldrslt\\\\cf2 ([^\\}]+)\\}\\}\n/','$1',$text);
	// разбор строк таблицы
	$text = preg_replace("/{$trStart}{$cellDef}{$cellDef}{$cellDef}{$cell}{$cell}{$cell}{$trEnd}/m","|$1|$2|$3|\n",$text);
	// разбор строк таблицы c объединенной первой ячейкой
	$text = preg_replace("/{$trStart}{$cellDef}{$cellDef}{$cellDef}\\\\intbl\\\\cell\n{$cell}{$cell}{$trEnd}/m","||$1|$2|\n",$text);
	// разбор строк таблицы c объединенной первой и последней ячейками
	$text = preg_replace("/{$trStart}{$cellDef}{$cellDef}{$cellDef}\\\\intbl\\\\cell\n{$cell}\\\\intbl\\\\cell\n{$trEnd}/m","||$1||\n",$text);
	// разбор строк таблицы c объединенной последней ячейкой
	$text = preg_replace("/{$trStart}{$cellDef}{$cellDef}{$cellDef}{$cell}{$cell}\\\\intbl\\\\cell\n{$trEnd}/m","|$1|$2||\n",$text);

	if (!strlen($text))
		return "";

	// Create empty stack array.
	$document = "";
	$stack = array();
	$j = -1;
	// Read the data character-by- character…
	for ($i = 0, $len = strlen($text); $i < $len; $i++) {
		
		$c = in_array($text[$i],['|'])
			? $text[$i]
			: iconv('cp1251','UTF-8//IGNORE',$text[$i]);

		// Depending on current character select the further actions.
		switch ($c) {
				// the most important key word backslash
			case "\\":
				// read next character
				$nc = $text[$i + 1];

				// If it is another backslash or nonbreaking space or hyphen,
				// then the character is plain text and add it to the output stream.
				if ($nc == '\\' && rtf_isPlainText($stack[$j] ?? null)) $document .= '\\';
				elseif ($nc == '~' && rtf_isPlainText($stack[$j] ?? null)) $document .= ' ';
				elseif ($nc == '_' && rtf_isPlainText($stack[$j] ?? null)) $document .= '-';
				// If it is an asterisk mark, add it to the stack.
				elseif ($nc == '*') $stack[$j]["*"] = true;
				// If it is a single quote, read next two characters that are the hexadecimal notation
				// of a character we should add to the output stream.
				elseif ($nc == "'") {
					$hex = substr($text, $i + 2, 2);
					if (rtf_isPlainText($stack[$j]))
						$document .= html_entity_decode("&#" . hexdec($hex) . ";");
					//Shift the pointer.
					$i += 2;
					// Since, we’ve found the alphabetic character, the next characters are control word
					// and, possibly, some digit parameter.
				} elseif ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
					$word = "";
					$param = null;

					// Start reading characters after the backslash.
					for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) {
						$nc = $text[$k];
						// If the current character is a letter and there were no digits before it,
						// then we’re still reading the control word. If there were digits, we should stop
						// since we reach the end of the control word.
						if ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') {
							if (empty($param))
								$word .= $nc;
							else
								break;
							// If it is a digit, store the parameter.
						} elseif ($nc >= '0' && $nc <= '9')
							$param .= $nc;
						// Since minus sign may occur only before a digit parameter, check whether
						// $param is empty. Otherwise, we reach the end of the control word.
						elseif ($nc == '-') {
							if (empty($param))
								$param .= $nc;
							else
								break;
						} else
							break;
					}
					// Shift the pointer on the number of read characters.
					$i += $m - 1;

					// Start analyzing what we’ve read. We are interested mostly in control words.
					$toText = "";
					switch (strtolower($word)) {
							// If the control word is "u", then its parameter is the decimal notation of the
							// Unicode character that should be added to the output stream.
							// We need to check whether the stack contains \ucN control word. If it does,
							// we should remove the N characters from the output stream.
						case "u":
							$toText .= html_entity_decode("&#x" . dechex($param) . ";");
							$ucDelta = @$stack[$j]["uc"];
							if ($ucDelta > 0)
								$i += $ucDelta;
							break;
							// Select line feeds, spaces and tabs.
						case "par":
						case "page":
						case "column":
						case "line":
						case "lbr":
							$toText .= "\n";
							break;
						case "emspace":
						case "enspace":
						case "qmspace":
							$toText .= " ";
							break;
						case "tab":
							$toText .= "\t";
							break;
							// Add current date and time instead of corresponding labels.
						case "chdate":
							$toText .= date("m.d.Y");
							break;
						case "chdpl":
							$toText .= date("l, j F Y");
							break;
						case "chdpa":
							$toText .= date("D, j M Y");
							break;
						case "chtime":
							$toText .= date("H:i:s");
							break;
							// Replace some reserved characters to their html analogs.
						case "emdash":
							$toText .= html_entity_decode("&mdash;");
							break;
						case "endash":
							$toText .= html_entity_decode("&ndash;");
							break;
						case "bullet":
							$toText .= html_entity_decode("&#149;");
							break;
						case "lquote":
							$toText .= html_entity_decode("&lsquo;");
							break;
						case "rquote":
							$toText .= html_entity_decode("&rsquo;");
							break;
						case "ldblquote":
							$toText .= html_entity_decode("&laquo;");
							break;
						case "rdblquote":
							$toText .= html_entity_decode("&raquo;");
							break;
							// Add all other to the control words stack. If a control word
							// does not include parameters, set &param to true.
						default:
							$stack[$j][strtolower($word)] = empty($param) ? true : $param;
							break;
					}
					// Add data to the output stream if required.
					if (rtf_isPlainText($stack[$j] ?? null))
						$document .= $toText;
				}

				$i++;
				break;
				// If we read the opening brace {, then new subgroup starts and we add
				// new array stack element and write the data from previous stack element to it.
			case "{":
				array_push($stack, $stack[$j++] ?? null);
				break;
				// If we read the closing brace }, then we reach the end of subgroup and should remove 
				// the last stack element.
			case "}":
				array_pop($stack);
				$j--;
				break;
				// Skip “trash”.
			case '\0':
			case '\r':
			case '\f':
			case '\n':
				break;
				// Add other data to the output stream if required.
			default:
				if (rtf_isPlainText($stack[$j] ?? null))
					$document .= $c;
				break;
		}
	}

	// метка начала таблиц
	$document = str_replace($replace = '|Код ТН ВЭД|Наименование позиции|Доп. ед. изм.|',"#TABLE_HEADER#\n".$replace."\n#TABLE_START#",$document);

	// Return result.
	return $document;
}

/**
 * Prettify output
 */
function vdump($v,$die = false)
{
	?><pre style="display:block; text-align:left; border:1px solid #ccc; padding:20px; margin:15px 0; background:#152735; color:#ccc; border-radius:10px; box-shadow:1px 1px 8px rgba(0,0,0,0.6);"><?php echo htmlspecialchars(print_r($v,1))?></pre><?php
	if($die) die;
}