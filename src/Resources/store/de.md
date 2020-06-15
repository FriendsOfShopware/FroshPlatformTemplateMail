Mit Template Mail kannst du deine Mail-Templates aus der Administration in dein Theme auslagern. 
Somit sind deine Mail-Templates versioniert, können voneinander erben und du profitierst von all den anderen tollen Dingen, die man in Twig machen kann. 
Dieses Plugin stellt außerdem einen Loader bereit, der dafür sorgt, dass andere Plugins weitere Dateitypen hinzufügen können. Im Plugin selbst werden aktuell Twig, [MJML](https://mjml.io/) und [Inky](https://foundation.zurb.com/emails/docs/inky.html) unterstützt.


## Wie benutze ich das Plugin?
Durch die Installation ändert sich erst mal nichts für dich. Du kannst anfangen die ersten Templates in dein Theme auszulagern.

### Beispiel Pfade für Twig, Zahlungsbestätigung (*order_transaction.state.paid*):
Ordner: custom/plugins/*MyTheme*/Resources/views/email/global/

- HTML-Inhalt der Zahlungsbestätigung: *order_transaction.state.paid*/html.twig
- PlainText-Inhalt der Zahlungsbestätigung: *order_transaction.state.paid*/plain.twig
- Betreff der Zahlungsbestätigung: *order_transaction.state.paid*/subject.twig


Das Plugin wird von der Github Organization [FriendsOfShopware](https://github.com/FriendsOfShopware/) entwickelt.
Maintainer des Plugin ist [Soner Sayakci](https://github.com/shyim).
Das Github Repository ist zu finden [hier](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail)
[Bei Fragen / Fehlern bitte ein Github Issue erstellen](https://github.com/FriendsOfShopware/FroshPlatformTemplateMail/issues/new)
