# Store Shopware mail templates in theme

[![codecov](https://codecov.io/gh/FriendsOfShopware/FroshPlatformTemplateMail/branch/master/graph/badge.svg?token=HUPWYZ80YS)](https://codecov.io/gh/FriendsOfShopware/FroshPlatformTemplateMail)
[![PHPUnit](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/actions/workflows/unit.yml/badge.svg)](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/actions/workflows/unit.yml)

This plugin allows to store the mails in theme instead of database. This gives us advantages like

* easier deployment
* translate it using snippets
* build your mail template using includes / extends / blocks / inheritance
* usage of theme configuration


## Requirements

- Shopware 6.6.0 or newer
- PHP 8.2

## Installation

- Download latest release
- Extract the zip file in `shopware_folder/custom/plugins/`

### Export Templates

You can use the following command to export the current templates to the file system of your theme `MyTheme` to start modifying them:

```shell
bin/console frosh:template-mail:export custom/plugins/MyTheme/src/Resources/views/email/
```

## Template location

Create a mail for a specific subshop or language shop (also inheritance in shops works)

Search order in example with sOrder:

custom/plugins/FroshPlatformTemplateMail/src/Resources/views/email/global/order_transaction.state.paid/html.twig
* HTML Template
  * `custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/html.twig` (Language Locale)
  * `custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/[ID]/html.twig` (Template ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/html.twig` (Saleschannel ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/html.twig` (Language ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/html.twig` (Default)
  * Database saved values (for right template names search in database table `mail_template_type`)
* Text Template
  * `custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/plain.twig` (Language Locale)
  * `custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/[ID]/plain.twig` (Template ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/plain.twig` (Saleschannel ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/plain.twig` (Language ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/plain.twig` (Default)
  * Database saved values (for right template names search in database table `mail_template_type`)
* Subject Template
  * `custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/subject.twig` (Language Code)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/[ID]/subject.twig` (Template ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/subject.twig` (Saleschannel ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/subject.twig` (Language ID)
  * `custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/subject.twig` (Default)
  * Database saved values (for right template names search in database table `mail_template_type`)

* You can also nest templates. E.g.:
  * `custom/plugins/MyTheme/src/Resources/views/email/[ID]/[en-GB]/order_transaction.state.paid/html.twig` (Saleschannel ID)/(Language Locale)

## MJML Support

The plugin supports [MJML](https://mjml.io/). In the standard configuration mail templates in MJML format are processed via the service https://mjml.shyim.de.

## Known Limitations

* The test mail function in the admin panel does not support the overwritten mail templates. ([#34](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/issues/34)).

## Contributing

Feel free to fork and send pull requests!

## Licence

This project uses the [MIT License](LICENCE.md).
