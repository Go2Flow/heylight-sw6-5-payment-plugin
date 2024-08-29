import HeyLightAPIService from "./module/heylight-heylight/HeyLightAPIService";
import "./service/HeyLightSettingsService";
import "./service/HeyLightOrderService";

const { Application } = Shopware;

const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'HeyLightAPIService',
    (container) => new HeyLightAPIService(initContainer.httpClient, container.loginService),
);

import "./init/svgs";

import './module/heylight-heylight';

import './module/sw-order/page/sw-order-list';
import './module/sw-order/page/sw-order-detail';
