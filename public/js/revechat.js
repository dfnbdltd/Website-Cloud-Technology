(function (window, document) {
  'use strict';

  var button = document.getElementById('reveChatFab');
  if (!button) return;

  var minimizeButton = document.getElementById('reveChatMinimize');
  var account = button.getAttribute('data-revechat-account');
  var scriptUrl = 'https://static.revechat.com/widget/scripts/new-livechat.js?v=20260809';
  var loader = null;
  var hideStyle = null;
  var stateWatcher = null;

  function hideReveButton() {
    if (hideStyle || !document.head) return;
    hideStyle = document.createElement('style');
    hideStyle.id = 'hosting-revechat-hide';
    hideStyle.textContent = '#reve-chat-widget-holder-2{visibility:hidden!important}';
    document.head.appendChild(hideStyle);
  }

  function maximizeWhenReady(attempt) {
    var reveApi = window.$_REVECHAT_API;
    try {
      if (reveApi && reveApi.Button && typeof reveApi.Button.maximize === 'function') {
        reveApi.Button.maximize();
        return;
      }
    } catch (error) {
      console.error('Could not open the Reve Chat widget:', error);
    }
    if (attempt < 80) window.setTimeout(function () { maximizeWhenReady(attempt + 1); }, 250);
  }

  function syncWidgetState() {
    var holder = document.getElementById('reve-chat-widget-holder');
    if (!holder) {
      button.hidden = false;
      if (minimizeButton) minimizeButton.hidden = true;
      return;
    }

    var bounds = holder.getBoundingClientRect();
    var style = window.getComputedStyle(holder);
    var isOpen = style.display !== 'none'
      && style.visibility !== 'hidden'
      && parseFloat(style.opacity) !== 0
      && bounds.width >= 200
      && bounds.height >= 200;
    button.hidden = isOpen;
    if (minimizeButton) {
      minimizeButton.hidden = !isOpen;
      if (isOpen) {
        minimizeButton.style.top = Math.max(8, bounds.top + 12) + 'px';
        minimizeButton.style.right = Math.max(8, window.innerWidth - bounds.right + 12) + 'px';
      }
    }
  }

  function minimizeReveChat() {
    var holder = document.getElementById('reve-chat-widget-holder');
    if (!holder) return;
    holder.style.visibility = 'hidden';
    holder.style.pointerEvents = 'none';
    syncWidgetState();
  }

  function watchWidgetState() {
    if (stateWatcher) return;
    syncWidgetState();
    stateWatcher = window.setInterval(syncWidgetState, 500);
  }

  function ensureReveApi() {
    if (window.$_REVECHAT_API) return window.$_REVECHAT_API;
    var queue = [];
    var api = function (callback) { queue.push(callback); };
    api._ = queue;
    window.$_REVECHAT_API = api;
    window.__revechat_account = account;
    window.__revechat_version = 2;
    return api;
  }

  function loadWidget() {
    if (loader) return loader;

    loader = new Promise(function (resolve, reject) {
      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.async = true;
      script.charset = 'utf-8';
      script.src = scriptUrl;
      script.onload = resolve;
      script.onerror = function () {
        script.remove();
        loader = null;
        reject(new Error('Reve Chat could not be loaded.'));
      };
      document.head.appendChild(script);
    });
    return loader;
  }

  window.openReveChat = function () {
    if (!account) {
      console.error('Reve Chat account ID is not configured.');
      return;
    }

    var holder = document.getElementById('reve-chat-widget-holder');
    if (holder) {
      holder.style.visibility = '';
      holder.style.pointerEvents = '';
    }
    button.hidden = false;
    hideReveButton();
    var reveApi = ensureReveApi();
    watchWidgetState();
    loadWidget().then(function () {
      maximizeWhenReady(0);
    }).catch(function (error) {
      window.clearInterval(stateWatcher);
      stateWatcher = null;
      button.hidden = false;
      console.error(error.message, error);
    });
    reveApi(function () {
      maximizeWhenReady(0);
    });
  };

  button.addEventListener('click', window.openReveChat);
  if (minimizeButton) minimizeButton.addEventListener('click', minimizeReveChat);
})(window, document);
