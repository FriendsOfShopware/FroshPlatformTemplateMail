# Store Shopware mail templates in theme

[![Join the chat at https://gitter.im/FriendsOfShopware/Lobby](https://badges.gitter.im/FriendsOfShopware/Lobby.svg)](https://gitter.im/FriendsOfShopware/Lobby)

This plugin allows to store the mails in theme instead of database. This gives us advantages like

* easier deployment
* translate it using snippets
* build your mail template using includes / extends / blocks / inheritance
* usage of theme configuration


## Requirements

- Shopware 6.1 or newer
- PHP 7.2


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
  * Database saved values
* Text Template
  * custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/plain.twig (Language Locale)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/plain.twig (Saleschannel ID)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/plain.twig (Language ID)
  * custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/plain.twig (Default)
  * Database saved values
* Subject Template
  * custom/plugins/MyTheme/src/Resources/views/email/[en-GB]/order_transaction.state.paid/subject.twig (Language Code)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/subject.twig (Saleschannel ID)
  * custom/plugins/MyTheme/src/Resources/views/email/[ID]/order_transaction.state.paid/subject.twig (Language ID)
  * custom/plugins/MyTheme/src/Resources/views/email/global/order_transaction.state.paid/subject.twig (Default)
  * Database saved values


## Contributing

Feel free to fork and send pull requests!


## Licence

This project uses the [MIT License](LICENCE.md).
