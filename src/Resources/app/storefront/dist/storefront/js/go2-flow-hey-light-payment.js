"use strict";(self.webpackChunk=self.webpackChunk||[]).push([["go2-flow-hey-light-payment"],{2654:(e,n,a)=>{var t=a(6285);class i extends t.Z{init(){const e=document.querySelector("[data-off-canvas-cart]");if(e instanceof HTMLElement){window.PluginManager.getPluginInstanceFromElement(e,"OffCanvasCart").$emitter.subscribe("offCanvasOpened",this.onOffCanvasOpened)}}onOffCanvasOpened(){document.getElementsByClassName("heidipay-container-2").length&&(window.HeyLightLoaded?initCoreHeyLightCode(jQuery):loadHeyLight())}}window.PluginManager.register("HeylightOpenMiniBasket",i)}},e=>{e.O(0,["vendor-node","vendor-shared"],(()=>{return n=2654,e(e.s=n);var n}));e.O()}]);