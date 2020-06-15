With Template Mail you can outsource your mail templates in the administration to your theme. 
So your mail templates are versioned, can inherit from each other and all the other great things you can do in Twig. 
The plugin also supports loaders. So other plugins can add file extensions where they can create the mail.
The plugin itself supports Twig and MJML by default.

## How do I use the plugin?

After installation nothing changes for you. You can now start to swap the first templates into your theme.

Example paths:

* custom/plugins/MyTheme/Resources/views/email/global/order_transaction.state.paid/html.twig - content of the sOrder in HTML form
* custom/plugins/MyTheme/Resources/views/email/global/order_transaction.state.paid/plain.twig - content of the sOrder in text form
* custom/plugins/MyTheme/Resources/views/email/global/order_transaction.state.paid/subject.twig - title of the sOrder Mailion)

The plugin is provided by the Github Organization [FriendsOfShopware](https://github.com/FriendsOfShopware/).
Maintainer of the plugin is [Soner Sayakci](https://github.com/shyim).
You can find the Github Repository [here](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail)
[For questions / errors please create a Github Issue](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/issues/new)
