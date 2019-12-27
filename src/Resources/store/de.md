Mit Template Mail kannst du deine Mail Templates in der Administration in dein Theme auslagern. 
Somit sind deine Mail Templates versioniert, können von einander erben und all die anderen Tollen Dinge die man in Twig tun kann. 
Das Plugin unterstüzt ebenfalls Loader. So können andere Plugins Dateiendungen hinzufügen, wo sie dann die Mail erstellen können.
Im Plugin selbst wird standardmäßig Twig und MJML unterstüzt.


## Wie benutze ich das Plugin?
Nach der Installation ändert sich erstmal nichts für dich. Du kannst nun Anfangen die ersten Templates auszulagern in dein Theme.

Beispiel Pfade:

* custom/plugins/MyTheme/Resources/views/email/global/order_transaction.state.paid/html.twig - Inhalt der sOrder in HTML Form
* custom/plugins/MyTheme/Resources/views/email/global/order_transaction.state.paid/text.twig - Inhalt der sOrder in Text Form
* custom/plugins/MyTheme/Resources/views/email/global/order_transaction.state.paid/subject.twig - Titel der sOrder Mail


Das Plugin wird von der Github Organization [FriendsOfShopware](https://github.com/FriendsOfShopware/) entwickelt.
Maintainer des Plugin ist [Soner Sayakci](https://github.com/shyim).
Das Github Repository ist zu finden [hier](https://github.com/FriendsOfShopware/FroshPlatformAdminer)
[Bei Fragen / Fehlern bitte ein Github Issue erstellen](https://github.com/FriendsOfShopware/FroshPlatformAdminer/issues/new
