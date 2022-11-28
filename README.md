# Store Shopware mail templates in theme

[![Open in Gitpod](https://gitpod.io/button/open-in-gitpod.svg)](https://gitpod.io/#https://github.com/FriendsOfShopware/FroshPlatformTemplateMail)

[![codecov](https://codecov.io/gh/FriendsOfShopware/FroshPlatformTemplateMail/branch/master/graph/badge.svg?token=HUPWYZ80YS)](https://codecov.io/gh/FriendsOfShopware/FroshPlatformTemplateMail)
[![PHPUnit](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/actions/workflows/unit.yml/badge.svg)](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/actions/workflows/unit.yml)
[![Slack](https://img.shields.io/badge/chat-on%20slack-%23ECB22E)](https://slack.shopware.com?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)

This plugin allows to store the mails in theme instead of database. This gives us advantages like

* easier deployment
* translate it using snippets
* build your mail template using includes / extends / blocks / inheritance
* usage of theme configuration


## Requirements

- Shopware 6.4.1 or newer
- PHP 7.4

## Installation

- Download latest release
- Extract the zip file in `shopware_folder/custom/plugins/`


## Template location

Create a mail for a specific subshop or language shop (also inheritance in shops works)

Search order in example with sOrder:

custom/plugins/FroshPlatformTemplateMail/src/Resources/views/email/global/order_transaction.state.paid/html.twig
* HTML Template
  * custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/html.twig (Language Locale)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/html.twig (Saleschannel ID)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/html.twig (Language ID)
  * custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/html.twig (Default)
  * Database saved values (for right template names search in database table "mail_template_type")
* Text Template
  * custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/plain.twig (Language Locale)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/plain.twig (Saleschannel ID)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/plain.twig (Language ID)
  * custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/plain.twig (Default)
  * Database saved values (for right template names search in database table "mail_template_type")
* Subject Template
  * custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/subject.twig (Language Code)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/subject.twig (Saleschannel ID)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/subject.twig (Language ID)
  * custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/subject.twig (Default)
  * Database saved values (for right template names search in database table "mail_template_type")

* You can also nest templates. E.g.:
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/[en-GB]/order_transaction.state.paid/html.twig (Saleschannel ID)/(Language Locale)

## Contributing

Feel free to fork and send pull requests!


## Licence

This project uses the [MIT License](LICENCE.md).
