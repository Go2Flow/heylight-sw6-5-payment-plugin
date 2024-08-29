import './components/heylight-settings-icon';
import './extension/sw-settings-index';
import './page/heylight-settings';
import './page/heylight-order-detail';

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

const { Module } = Shopware;


// Here you create your new route, refer to the mentioned guide for more information
Module.register('heylight-heylight', {
    type: 'plugin',
    name: 'HeyLight',
    title: 'HeyLight.mainMenuItemGeneral',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#2b52ff',
    icon: 'regular-cog',

    snippets: {
        'de-DE': deDE,
        'de-CH': deDE,
        'en-GB': enGB,
    },

    routes: {
        index: {
            component: 'heylight-settings',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    },
    settingsItem: {
        name: 'heylight-settings',
        group: 'plugins',
        to: 'heylight.heylight.index',
        iconComponent: 'heylight-settings-icon',
        backgroundEnabled: true,
    },

    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.order.detail') {
            currentRoute.children.push({
                component: 'heylight-order-detail',
                name: 'heylight.order.detail',
                path: '/sw/order/detail/:id/heylight/:transaction',
                meta: {
                    parentPath: "sw.order.index",
                    privilege: 'order.viewer',
                }
            });
        }
        next(currentRoute);
    },
});
