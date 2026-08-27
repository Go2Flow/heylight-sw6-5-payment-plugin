# 2.0.1
- Fix: payment amount sent to HeyLight no longer includes the promotion widget fee (ENTW-3424)
- Fix: automatic order status sync task was a no-op (wrong array lookup, wrong response field name) and could leak a previous order's status onto a following order (ENTW-3424)
- Fix: checkout returned a fatal error instead of a clean payment-failed message when HeyLight returned an invalid payment session (ENTW-3424)
- Fix: removed dead/broken settings-page route override in the administration (ENTW-3424)
# 2.0.0
- Compatibility with Shopware 6.7 (new payment handler API, attribute routing, Vue 3 administration)
# 1.0.2
- Fixed a Bug where an order hat multiple webhooks and thus could not be validated correctly
# 1.0.1
- Fix for older Shopware 6.5 Versions
# 1.0.0
- Initial Version
