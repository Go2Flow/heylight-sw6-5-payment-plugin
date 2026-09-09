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

