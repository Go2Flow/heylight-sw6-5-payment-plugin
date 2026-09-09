# 2.0.4
- Fix: corrected a typo in the administration settings validation (`romotionWidgetFee` -> `promotionWidgetFee`), which had left the promotion widget fee field unvalidated (ENTW-3424)
- Added a minimal PHPUnit test suite for the status-mapping and API response validation logic (ENTW-3424); see README.md for how to run it
# 2.0.3
- Fix: scheduled order status sync stopped tracking orders once HeyLight moved them to a "waiting" state (in_progress); now keeps syncing until a final status is reached (ENTW-3424)
- Fix: an unknown/unmapped HeyLight status was treated as DECLINED and could wrongly cancel a valid order; unknown statuses are now logged and left untouched instead (ENTW-3424)
- Fix: getOrderStatus() no longer crashes on an invalid/unexpected API response; the response is validated first and errors are logged (ENTW-3424)
- Fix: a failed delivery confirmation to HeyLight is now logged instead of silently ignored (ENTW-3424)
- Fix: payment type (BNPL/Credit) selection now uses the transaction actually being processed instead of the order's last transaction, preventing the wrong type from being sent to HeyLight on orders with multiple payment attempts (ENTW-3424)
# 2.0.2
- Fix: HeyLight widget on product detail page and cart did not render because currency/language were read from a non-existent template path (`page.header.*`), resulting in empty attributes (ENTW-3424)
- Fix: HeyLight widget in the offcanvas mini-cart failed to initialize due to a call to a non-existent SDK function (`initCoreHeyLightCode`); corrected to the actual SDK entry point (`initCoreHeidiCode`) (ENTW-3424)
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
