# Парсер справочника ТН ВЭД из rtf документа

Справочник товарной номенклатуры внешнеэкономической деятельности (ТН ВЭД) доступен через СМЭВ [https://smev3.gosuslugi.ru/portal/inquirytype_one.jsp?id=40676&zone=fed&page=1&dTest=false](https://smev3.gosuslugi.ru/portal/inquirytype_one.jsp?id=40676&zone=fed&page=1&dTest=false), но что, если к СМЭВ нет доступа? В этом случае получить справочник в приемлемом машиночитаемом виде оказалось затруднительно, единственный официальный источник, который я обнаружил был ресурс [https://www.nalog.gov.ru/rn77/program/5961290/](https://www.nalog.gov.ru/rn77/program/5961290/), но на момент, когда я обратился к этому сайту данные в архиве, мягко говоря, были не полными.

В итоге я обратился к исходному документу на сайте Консультант [http://www.consultant.ru/document/cons_doc_LAW_133442/](http://www.consultant.ru/document/cons_doc_LAW_401174/), скачал его в формате RTF и распарсил справочник ТНВЭД в XML.

**[Демо работы парсера](https://pgood.space/userfiles/file/tnved-parser/)**

## Требования

PHP 7.4 или выше. В некоторых случаях может понадобится увеличить максимальное время работы скрипта, т.к. преобразование RTF в текст может занять длительное время, если сервер не очень производительный.
