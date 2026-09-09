# 2.0.4
- Fehlerbehebung: Tippfehler in der Admin-Einstellungsvalidierung korrigiert (`romotionWidgetFee` -> `promotionWidgetFee`), wodurch das Feld für die Promotion-Widget-Fee bisher nicht validiert wurde (ENTW-3424)
- Minimale PHPUnit-Testsuite für Statusmapping und API-Antwortvalidierung ergänzt (ENTW-3424); siehe README.md zur Ausführung
# 2.0.3
- Fehlerbehebung: Der automatische Statusabgleich verlor Bestellungen aus den Augen, sobald HeyLight sie in einen wartenden Status (in_progress) versetzte; wird nun bis zum Erreichen eines Endstatus weiter abgeglichen (ENTW-3424)
- Fehlerbehebung: Ein unbekannter/nicht zugeordneter HeyLight-Status wurde als DECLINED behandelt und konnte gültige Bestellungen fälschlich stornieren; unbekannte Status werden nun geloggt und die Transaktion bleibt unverändert (ENTW-3424)
- Fehlerbehebung: getOrderStatus() stürzt nicht mehr bei einer ungültigen/unerwarteten API-Antwort ab; die Antwort wird vorher validiert und Fehler werden geloggt (ENTW-3424)
- Fehlerbehebung: Eine fehlgeschlagene Lieferbestätigung an HeyLight wird nun geloggt statt stillschweigend ignoriert (ENTW-3424)
- Fehlerbehebung: Die Auswahl der Zahlungsart (BNPL/Credit) verwendet nun die tatsächlich verarbeitete Transaktion statt der letzten Transaktion der Bestellung, wodurch bei mehreren Zahlungsversuchen nicht mehr die falsche Zahlungsart an HeyLight übermittelt wird (ENTW-3424)
# 2.0.2
- Fehlerbehebung: Das HeyLight-Widget auf der Produktdetailseite und im Warenkorb wurde nicht angezeigt, da Währung/Sprache über einen nicht existierenden Template-Pfad (`page.header.*`) ausgelesen wurden, was zu leeren Attributen führte (ENTW-3424)
- Fehlerbehebung: Das HeyLight-Widget im Offcanvas-Mini-Warenkorb konnte nicht initialisiert werden, da eine nicht existierende SDK-Funktion (`initCoreHeyLightCode`) aufgerufen wurde; korrigiert auf den tatsächlichen SDK-Einstiegspunkt (`initCoreHeidiCode`) (ENTW-3424)
# 2.0.1
- Fehlerbehebung: Der an HeyLight übermittelte Zahlungsbetrag enthält nicht mehr die Promotion-Widget-Fee (ENTW-3424)
- Fehlerbehebung: Der automatische Bestellstatusabgleich war ein No-op (falscher Array-Zugriff, falscher Response-Feldname) und konnte den Status einer Bestellung auf eine nachfolgende übertragen (ENTW-3424)
- Fehlerbehebung: Der Checkout lieferte einen technischen Fehler statt einer sauberen Zahlungsfehlermeldung, wenn HeyLight keine gültige Zahlungs-Session zurückgab (ENTW-3424)
- Fehlerbehebung: Tote/defekte Einstellungsseiten-Routen-Erweiterung in der Administration entfernt (ENTW-3424)
# 2.0.0
- Kompatibilität mit Shopware 6.7 (neue Payment-Handler-API, Attribut-Routing, Vue-3-Administration)
# 1.0.2
- Fehlerbehebung: Wenn eine Bestellung mehrere Webhooks hatte konnte der Webhook nicht korrekt validiert werden
# 1.0.1
- Fix für ältere Shopware 6.5 Versionen
# 1.0.0
- Initiale Version

