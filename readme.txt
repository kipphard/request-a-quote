=== Angebotsanfrage – Request a Quote für WooCommerce ===
Contributors: andrekipphard
Tags: woocommerce, request a quote, b2b, wholesale, angebotsanfrage
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 0.1.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ermöglicht B2B- und Großhandels-Kunden, Produkte in eine Angebotsliste zu legen und eine Anfrage abzuschicken – ohne Preise preiszugeben.

== Description ==

**Angebotsanfrage** ist ein schlankes WooCommerce-Plugin für B2B- und Großhandels-Shops, die keine öffentlichen Preise zeigen möchten oder individuelle Angebote erstellen wollen.

**Wie es funktioniert:**

1. Der Kunde sieht auf Produkt- und Shop-Seiten einen „Angebot anfragen"-Button.
2. Produkte landen in einer **Angebotsliste** (ähnlich dem Warenkorb) – ohne Preis.
3. Über den Shortcode `[angebotsanfrage]` schickt der Kunde eine Anfrage ab (Name, E-Mail, Nachricht u. a. – konfigurierbar).
4. Der Shop-Inhaber erhält eine E-Mail mit allen Details. Die Anfrage wird als eigener Beitragstyp im Admin gespeichert.

**Kostenlose Funktionen:**

* „Angebot anfragen"-Button neben oder anstelle des Warenkorb-Buttons
* Sitzungsbasierte Angebotsliste (WooCommerce Session)
* Konfigurierbare Formularfelder (Name, Unternehmen, Telefon, Nachricht)
* Preise optional ausblenden (CSS)
* Anfragen als Custom Post Type `aga_quote` gespeichert
* Admin- und Kunden-E-Mail-Benachrichtigung
* Shortcode `[angebotsanfrage]` für jede Seite
* Anwenden auf alle Produkte oder nur bestimmte Kategorien

**Angebotsanfrage Pro:**

* Angebot direkt in eine WooCommerce-Bestellung (Entwurf) umwandeln
* PDF-Angebotsdokument generieren
* Benutzerdefinierte Formularfelder

→ [Jetzt upgraden](https://products.kipphard.com/angebotsanfrage)

**Warum nicht YITH Request a Quote?**

Ehrlicher Umfang, keine aufgeblähten Abhängigkeiten, klare Trennung zwischen freier und Pro-Version.

== Installation ==

1. Plugin hochladen und aktivieren.
2. WooCommerce muss installiert und aktiv sein.
3. Unter **WooCommerce → Angebotsanfrage** die Einstellungen anpassen.
4. Shortcode `[angebotsanfrage]` auf einer Seite einfügen (z. B. „Angebotsliste").

== Frequently Asked Questions ==

= Benötige ich WooCommerce? =

Ja, WooCommerce ist Pflicht. Das Plugin zeigt einen Hinweis und deaktiviert sich selbst, wenn WooCommerce fehlt.

= Kann ich den Button-Text ändern? =

Ja, unter WooCommerce → Angebotsanfrage → Button-Beschriftung.

= Kann ich Preise ausblenden? =

Ja, die Option „Preise ausblenden" blendet `.price`-Elemente per CSS aus.

= Werden Anfragen gespeichert? =

Ja, jede Anfrage wird als Beitragstyp `aga_quote` im WordPress-Admin gespeichert.

== Screenshots ==

1. Einstellungsseite im WordPress-Admin
2. „Angebot anfragen"-Button auf der Produktseite
3. Angebotsliste mit Formular (Shortcode)
4. Angebotsanfrage im Admin-Backend

== Changelog ==

= 0.1.0 =
* Erstveröffentlichung.

== Upgrade Notice ==

= 0.1.0 =
Erstveröffentlichung.
