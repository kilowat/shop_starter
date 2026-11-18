# Схема БД Bitrix (ключевые таблицы проекта)

> Сгенерировано `scripts/db-schema.php` из `information_schema`. Пересобрать после миграций/обновлений.
> Полный список таблиц: `SHOW TABLES`. Здесь только то, что нужно в 90% задач.

## main

### b_user

```
ID int PK
TIMESTAMP_X timestamp null
LOGIN varchar(50)
PASSWORD varchar(255)
CHECKWORD varchar(255) null
ACTIVE char(1)
NAME varchar(50) null
LAST_NAME varchar(50) null
EMAIL varchar(255) null
LAST_LOGIN datetime null
DATE_REGISTER datetime
LID char(2) null
PERSONAL_PROFESSION varchar(255) null
PERSONAL_WWW varchar(255) null
PERSONAL_ICQ varchar(255) null
PERSONAL_GENDER char(1) null
PERSONAL_BIRTHDATE varchar(50) null
PERSONAL_PHOTO int null
PERSONAL_PHONE varchar(255) null
PERSONAL_FAX varchar(255) null
PERSONAL_MOBILE varchar(255) null
PERSONAL_PAGER varchar(255) null
PERSONAL_STREET text null
PERSONAL_MAILBOX varchar(255) null
PERSONAL_CITY varchar(255) null
PERSONAL_STATE varchar(255) null
PERSONAL_ZIP varchar(255) null
PERSONAL_COUNTRY varchar(255) null
PERSONAL_NOTES text null
WORK_COMPANY varchar(255) null
WORK_DEPARTMENT varchar(255) null
WORK_POSITION varchar(255) null
WORK_WWW varchar(255) null
WORK_PHONE varchar(255) null
WORK_FAX varchar(255) null
WORK_PAGER varchar(255) null
WORK_STREET text null
WORK_MAILBOX varchar(255) null
WORK_CITY varchar(255) null
WORK_STATE varchar(255) null
WORK_ZIP varchar(255) null
WORK_COUNTRY varchar(255) null
WORK_PROFILE text null
WORK_LOGO int null
WORK_NOTES text null
ADMIN_NOTES text null
STORED_HASH varchar(32) null
XML_ID varchar(255) null
PERSONAL_BIRTHDAY date null
EXTERNAL_AUTH_ID varchar(255) null
CHECKWORD_TIME datetime null
SECOND_NAME varchar(50) null
CONFIRM_CODE varchar(8) null
LOGIN_ATTEMPTS int null
LAST_ACTIVITY_DATE datetime null
AUTO_TIME_ZONE char(1) null
TIME_ZONE varchar(50) null
TIME_ZONE_OFFSET int null
TITLE varchar(255) null
BX_USER_ID varchar(32) null
LANGUAGE_ID char(2) null
BLOCKED char(1)
PASSWORD_EXPIRED char(1)
```

### b_group

```
ID int PK
TIMESTAMP_X timestamp null
ACTIVE char(1)
C_SORT int
ANONYMOUS char(1)
IS_SYSTEM char(1)
NAME varchar(255)
DESCRIPTION varchar(255) null
SECURITY_POLICY text null
STRING_ID varchar(255) null
```

### b_user_group

```
USER_ID int PK
GROUP_ID int PK
DATE_ACTIVE_FROM datetime null
```

### b_lang

```
LID char(2) PK
SORT int
DEF char(1)
ACTIVE char(1)
NAME varchar(50)
DIR varchar(50)
FORMAT_DATE varchar(50) null
FORMAT_DATETIME varchar(50) null
FORMAT_NAME varchar(255) null
WEEK_START int null
CHARSET varchar(255) null
LANGUAGE_ID char(2)
DOC_ROOT varchar(255) null
DOMAIN_LIMITED char(1)
SERVER_NAME varchar(255) null
SITE_NAME varchar(255) null
EMAIL varchar(255) null
CULTURE_ID int null
```

### b_module

```
ID varchar(50) PK
DATE_ACTIVE timestamp null
```

### b_file

```
ID int PK
TIMESTAMP_X timestamp null
MODULE_ID varchar(50) null
HEIGHT int null
WIDTH int null
FILE_SIZE bigint null
CONTENT_TYPE varchar(255) null
SUBDIR varchar(255) null
FILE_NAME varchar(255)
ORIGINAL_NAME varchar(255) null
DESCRIPTION varchar(255) null
HANDLER_ID varchar(50) null
EXTERNAL_ID varchar(50) null
```

## iblock

### b_iblock

```
ID int PK
TIMESTAMP_X datetime
IBLOCK_TYPE_ID varchar(50)
LID char(2)
CODE varchar(50) null
API_CODE varchar(50) null
REST_ON char(1)
NAME varchar(255)
ACTIVE char(1)
SORT int
LIST_PAGE_URL varchar(255) null
DETAIL_PAGE_URL varchar(255) null
SECTION_PAGE_URL varchar(255) null
CANONICAL_PAGE_URL varchar(255) null
PICTURE int null
DESCRIPTION text null
DESCRIPTION_TYPE char(4)
RSS_TTL int
RSS_ACTIVE char(1)
RSS_FILE_ACTIVE char(1)
RSS_FILE_LIMIT int null
RSS_FILE_DAYS int null
RSS_YANDEX_ACTIVE char(1)
XML_ID varchar(255) null
TMP_ID varchar(40) null
INDEX_ELEMENT char(1)
INDEX_SECTION char(1)
WORKFLOW char(1)
BIZPROC char(1)
SECTION_CHOOSER char(1) null
LIST_MODE char(1) null
RIGHTS_MODE char(1) null
SECTION_PROPERTY char(1) null
PROPERTY_INDEX char(1) null
VERSION int
LAST_CONV_ELEMENT int
SOCNET_GROUP_ID int null
EDIT_FILE_BEFORE varchar(255) null
EDIT_FILE_AFTER varchar(255) null
SECTIONS_NAME varchar(100) null
SECTION_NAME varchar(100) null
ELEMENTS_NAME varchar(100) null
ELEMENT_NAME varchar(100) null
FULLTEXT_INDEX varchar(1)
```

### b_iblock_element

```
ID int PK
TIMESTAMP_X datetime null
MODIFIED_BY int null
DATE_CREATE datetime null
CREATED_BY int null
IBLOCK_ID int
IBLOCK_SECTION_ID int null
ACTIVE char(1)
ACTIVE_FROM datetime null
ACTIVE_TO datetime null
SORT int
NAME varchar(255)
PREVIEW_PICTURE int null
PREVIEW_TEXT text null
PREVIEW_TEXT_TYPE varchar(4)
DETAIL_PICTURE int null
DETAIL_TEXT longtext null
DETAIL_TEXT_TYPE varchar(4)
SEARCHABLE_CONTENT text null
WF_STATUS_ID int null
WF_PARENT_ELEMENT_ID int null
WF_NEW char(1) null
WF_LOCKED_BY int null
WF_DATE_LOCK datetime null
WF_COMMENTS text null
IN_SECTIONS char(1)
XML_ID varchar(255) null
CODE varchar(255) null
TAGS varchar(255) null
TMP_ID varchar(40) null
WF_LAST_HISTORY_ID int null
SHOW_COUNTER int null
SHOW_COUNTER_START datetime null
```

### b_iblock_property

```
ID int PK
TIMESTAMP_X datetime
IBLOCK_ID int
NAME varchar(255)
ACTIVE char(1)
SORT int
CODE varchar(50) null
DEFAULT_VALUE text null
PROPERTY_TYPE char(1)
ROW_COUNT int
COL_COUNT int
LIST_TYPE char(1)
MULTIPLE char(1)
XML_ID varchar(100) null
FILE_TYPE varchar(200) null
MULTIPLE_CNT int null
TMP_ID varchar(40) null
LINK_IBLOCK_ID int null
WITH_DESCRIPTION char(1) null
SEARCHABLE char(1)
FILTRABLE char(1)
IS_REQUIRED char(1) null
VERSION int
USER_TYPE varchar(255) null
USER_TYPE_SETTINGS text null
HINT varchar(255) null
```

### b_iblock_element_property

```
ID bigint PK
IBLOCK_PROPERTY_ID int
IBLOCK_ELEMENT_ID int
VALUE text
VALUE_TYPE char(4)
VALUE_ENUM int null
VALUE_NUM decimal(18,4) null
DESCRIPTION varchar(255) null
```

### b_iblock_section

```
ID int PK
TIMESTAMP_X datetime
MODIFIED_BY int null
DATE_CREATE datetime null
CREATED_BY int null
IBLOCK_ID int
IBLOCK_SECTION_ID int null
ACTIVE char(1)
GLOBAL_ACTIVE char(1)
SORT int
NAME varchar(255)
PICTURE int null
LEFT_MARGIN int null
RIGHT_MARGIN int null
DEPTH_LEVEL int null
DESCRIPTION text null
DESCRIPTION_TYPE char(4)
SEARCHABLE_CONTENT text null
CODE varchar(255) null
XML_ID varchar(255) null
TMP_ID varchar(40) null
DETAIL_PICTURE int null
SOCNET_GROUP_ID int null
```

## sale

### b_sale_order

```
ID int PK
LID char(2)
PERSON_TYPE_ID int
PAYED char(1)
DATE_PAYED datetime null
EMP_PAYED_ID int null
CANCELED char(1)
DATE_CANCELED datetime null
EMP_CANCELED_ID int null
REASON_CANCELED varchar(255) null
STATUS_ID varchar(2)
DATE_STATUS datetime
EMP_STATUS_ID int null
PRICE_DELIVERY decimal(26,8)
PRICE_PAYMENT decimal(26,8)
ALLOW_DELIVERY char(1)
DATE_ALLOW_DELIVERY datetime null
EMP_ALLOW_DELIVERY_ID int null
DEDUCTED char(1)
DATE_DEDUCTED datetime null
EMP_DEDUCTED_ID int null
REASON_UNDO_DEDUCTED varchar(255) null
MARKED char(1)
DATE_MARKED datetime null
EMP_MARKED_ID int null
REASON_MARKED varchar(255) null
RESERVED char(1)
PRICE decimal(26,8)
CURRENCY char(3)
DISCOUNT_VALUE decimal(26,8)
USER_ID int
PAY_SYSTEM_ID int null
DELIVERY_ID varchar(50) null
DATE_INSERT datetime
DATE_UPDATE datetime
USER_DESCRIPTION varchar(2000) null
ADDITIONAL_INFO varchar(255) null
PS_STATUS char(1) null
PS_STATUS_CODE char(5) null
PS_STATUS_DESCRIPTION varchar(250) null
PS_STATUS_MESSAGE varchar(250) null
PS_SUM decimal(26,8) null
PS_CURRENCY char(3) null
PS_RESPONSE_DATE datetime null
COMMENTS text null
TAX_VALUE decimal(26,8)
STAT_GID varchar(255) null
SUM_PAID decimal(26,8)
IS_RECURRING char(1)
RECURRING_ID int null
PAY_VOUCHER_NUM varchar(20) null
PAY_VOUCHER_DATE date null
LOCKED_BY int null
DATE_LOCK datetime null
RECOUNT_FLAG char(1)
AFFILIATE_ID int null
DELIVERY_DOC_NUM varchar(20) null
DELIVERY_DOC_DATE date null
UPDATED_1C char(1)
STORE_ID int null
ORDER_TOPIC varchar(255) null
CREATED_BY int null
RESPONSIBLE_ID int null
COMPANY_ID int null
DATE_PAY_BEFORE datetime null
DATE_BILL datetime null
ACCOUNT_NUMBER varchar(100) null
TRACKING_NUMBER varchar(255) null
XML_ID varchar(255) null
ID_1C varchar(36) null
VERSION_1C varchar(15) null
VERSION int
EXTERNAL_ORDER char(1)
RUNNING char(1)
BX_USER_ID varchar(32) null
SEARCH_CONTENT mediumtext null
IS_SYNC_B24 char(1)
```

### b_sale_basket

```
ID int PK
FUSER_ID int
ORDER_ID int null
PRODUCT_ID int
PRODUCT_PRICE_ID int null
PRICE_TYPE_ID int null
PRICE decimal(26,8)
CURRENCY char(3)
BASE_PRICE decimal(26,8) null
VAT_INCLUDED char(1)
DATE_INSERT datetime
DATE_UPDATE datetime
DATE_REFRESH datetime null
WEIGHT double(18,2) null
QUANTITY double(18,4)
LID char(2)
DELAY char(1)
NAME varchar(255)
CAN_BUY char(1)
MARKING_CODE_GROUP varchar(100) null
MODULE varchar(100) null
CALLBACK_FUNC varchar(100) null
NOTES varchar(250) null
ORDER_CALLBACK_FUNC varchar(100) null
DETAIL_PAGE_URL varchar(250) null
DISCOUNT_PRICE decimal(26,8)
CANCEL_CALLBACK_FUNC varchar(100) null
PAY_CALLBACK_FUNC varchar(100) null
PRODUCT_PROVIDER_CLASS varchar(100) null
CATALOG_XML_ID varchar(100) null
PRODUCT_XML_ID varchar(100) null
DISCOUNT_NAME varchar(255) null
DISCOUNT_VALUE char(32) null
DISCOUNT_COUPON char(32) null
VAT_RATE decimal(18,4) null
SUBSCRIBE char(1)
DEDUCTED char(1)
RESERVED char(1)
BARCODE_MULTI char(1)
RESERVE_QUANTITY double null
CUSTOM_PRICE char(1)
DIMENSIONS varchar(255) null
TYPE int null
SET_PARENT_ID int null
MEASURE_CODE int null
MEASURE_NAME varchar(50) null
RECOMMENDATION varchar(40) null
XML_ID varchar(255) null
SORT int
```

### b_sale_order_props_value

```
ID int PK
ORDER_ID int
ORDER_PROPS_ID int null
NAME varchar(255)
VALUE varchar(500) null
CODE varchar(50) null
XML_ID varchar(255) null
ENTITY_ID int
ENTITY_TYPE varchar(255)
```

### b_sale_status

```
ID varchar(2) PK
TYPE char(1)
SORT int
NOTIFY char(1)
COLOR varchar(10) null
XML_ID varchar(255) null
```

## catalog

### b_catalog_product

```
ID int PK
QUANTITY double
QUANTITY_TRACE char(1)
WEIGHT double
TIMESTAMP_X datetime
PRICE_TYPE char(1)
RECUR_SCHEME_LENGTH int null
RECUR_SCHEME_TYPE char(1)
TRIAL_PRICE_ID int null
WITHOUT_ORDER char(1)
SELECT_BEST_PRICE char(1)
VAT_ID int null
VAT_INCLUDED char(1) null
CAN_BUY_ZERO char(1)
NEGATIVE_AMOUNT_TRACE char(1)
TMP_ID varchar(40) null
PURCHASING_PRICE decimal(18,2) null
PURCHASING_CURRENCY char(3) null
BARCODE_MULTI char(1)
QUANTITY_RESERVED double null
SUBSCRIBE char(1) null
WIDTH double null
LENGTH double null
HEIGHT double null
MEASURE int null
TYPE int null
AVAILABLE char(1) null
BUNDLE char(1) null
```

### b_catalog_price

```
ID int PK
PRODUCT_ID int
EXTRA_ID int null
CATALOG_GROUP_ID int
PRICE decimal(18,2)
CURRENCY char(3)
TIMESTAMP_X datetime
QUANTITY_FROM int null
QUANTITY_TO int null
TMP_ID varchar(40) null
PRICE_SCALE decimal(26,12) null
```

### b_catalog_group

```
ID int PK
NAME varchar(100)
BASE char(1)
SORT int
XML_ID varchar(255) null
TIMESTAMP_X datetime null
MODIFIED_BY int null
DATE_CREATE datetime null
CREATED_BY int null
```

## Отсутствуют в этой базе

- b_site, b_sale_pay_system, b_sale_delivery

