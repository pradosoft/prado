function WebForm_PostBackOptions(eventTarget, eventArgument, validation, validationGroup, actionUrl, trackFocus, clientSubmit) {
    this.eventTarget = eventTarget;
    this.eventArgument = eventArgument;
    this.validation = validation;
    this.validationGroup = validationGroup;
    this.actionUrl = actionUrl;
    this.trackFocus = trackFocus;
    this.clientSubmit = clientSubmit;
}
function WebForm_DoPostBackWithOptions(options) {
    var validationResult = true;
    if (options.validation) {
        if (typeof(Page_ClientValidate) == 'function') {
            validationResult = Page_ClientValidate(options.validationGroup);
        }
    }
    if (validationResult) {
        if ((typeof(options.actionUrl) != "undefined") && (options.actionUrl != null) && (options.actionUrl.length > 0)) {
            theForm.action = options.actionUrl;
        }
        if (options.trackFocus) {
            var lastFocus = theForm.elements["__LASTFOCUS"];
            if ((typeof(lastFocus) != "undefined") && (lastFocus != null)) {
                if (typeof(document.activeElement) == "undefined") {
                    lastFocus.value = options.eventTarget;
                }
                else {
                    var active = document.activeElement;
                    if ((typeof(active.id) != "undefined") && (active != null)) {
                        if ((typeof(active.id) != "undefined") && (active.id != null) && (active.id.length > 0)) {
                            lastFocus.value = active.id;
                        }
                        else if (typeof(active.name) != "undefined") {
                            lastFocus.value = active.name;
                        }
                    }
                }
            }
        }
    }
    if (options.clientSubmit) {
        __doPostBack(options.eventTarget, options.eventArgument);
    }
}
var __callbackObject = new Object();
function WebForm_DoCallback(eventTarget, eventArgument, eventCallback, context, errorCallback, useAsync) {
    var postData = __theFormPostData +
                "__CALLBACKID=" + WebForm_EncodeCallback(eventTarget) +
                "&__CALLBACKPARAM=" + WebForm_EncodeCallback(eventArgument);
    var xmlRequest;
    var usePost = false;
    if (__nonMSDOMBrowser) {
        // http:
        // And: http:
        xmlRequest = new XMLHttpRequest();
        if (pageUrl.length + postData.length + 1 > 10000) {
            usePost = true;
        }
        if (usePost) {
            xmlRequest.open("POST", pageUrl, false);
            xmlRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xmlRequest.send(postData);
        }
        else {
            if (pageUrl.indexOf("?") != -1) {
                xmlRequest.open("GET", pageUrl + "&" + postData, false);
            }
            else {
                xmlRequest.open("GET", pageUrl + "?" + postData, false);
            }
            xmlRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xmlRequest.send(null);
        }
        var response = xmlRequest.responseText;
        if (response.charAt(0) == "s") {
            if ((typeof(eventCallback) != "undefined") && (eventCallback != null)) {
                eventCallback(response.substring(1), context);
            }
        }
        else {
            if ((typeof(errorCallback) != "undefined") && (errorCallback != null)) {
                errorCallback(response.substring(1), context);
            }
        }
    }
    else {
        xmlRequest = new ActiveXObject("Microsoft.XMLHTTP");
        xmlRequest.onreadystatechange = WebForm_CallbackComplete;
        __callbackObject.xmlRequest = xmlRequest;
        __callbackObject.eventCallback = eventCallback;
        __callbackObject.context = context;
        __callbackObject.errorCallback = errorCallback;
        if (pageUrl.length + postData.length + 1 > 2067) {
            usePost = true;
        }
        if (usePost) {
            xmlRequest.open("POST", pageUrl, useAsync);
            xmlRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xmlRequest.send(postData);
        }
        else {
            if (pageUrl.indexOf("?") != -1) {
                xmlRequest.open("GET", pageUrl + "&" + postData, useAsync);
            }
            else {
                xmlRequest.open("GET", pageUrl + "?" + postData, useAsync);
            }
            xmlRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xmlRequest.send();
        }
    }
}
function WebForm_CallbackComplete() {
    if (__callbackObject.xmlRequest.readyState == 4) {
        var response = __callbackObject.xmlRequest.responseText;
        if (response.charAt(0) == "s") {
            if ((typeof(__callbackObject.eventCallback) != "undefined") && (__callbackObject.eventCallback != null)) {
                __callbackObject.eventCallback(response.substring(1), __callbackObject.context);
            }
        }
        else {
            if ((typeof(__callbackObject.errorCallback) != "undefined") && (__callbackObject.errorCallback != null)) {
                __callbackObject.errorCallback(response.substring(1), __callbackObject.context);
            }
        }
    }
}
var __nonMSDOMBrowser = (window.navigator.appName.toLowerCase().indexOf('explorer') == -1);
var __theFormPostData = "";
function WebForm_InitCallback() {
    var count = theForm.elements.length;
    var element;
    for (var i = 0; i < count; i++) {
        element = theForm.elements[i];
        var tagName = element.tagName.toLowerCase();
        if (tagName == "input") {
            var type = element.type;
            if (type == "text" || type == "hidden" || type == "password" ||
                ((type == "checkbox" || type == "radio") && element.checked)) {
                __theFormPostData += element.name + "=" + WebForm_EncodeCallback(element.value) + "&";
            }
        }
        else if (tagName == "select") {
            var selectCount = element.children.length;
            for (var j = 0; j < selectCount; j++) {
                var selectChild = element.children[j];
                if ((selectChild.tagName.toLowerCase() == "option") && (selectChild.selected == true)) {
                    __theFormPostData += element.name + "=" + WebForm_EncodeCallback(selectChild.value) + "&";
                }
            }
        }
        else if (tagName == "textarea") {
            __theFormPostData += element.name + "=" + WebForm_EncodeCallback(element.value) + "&";
        }
    }
}
function WebForm_EncodeCallback(parameter) {
    if (encodeURIComponent) {
        return encodeURIComponent(parameter);
    }
    else {
        return escape(parameter);
    }
}
var __disabledControlArray = new Array();
function WebForm_ReEnableControls() {
    if (typeof(__enabledControlArray) == 'undefined') {
        return false;
    }
    var disabledIndex = 0;
    for (var i = 0; i < __enabledControlArray.length; i++) {
        var c;
        if (__nonMSDOMBrowser) {
            c = document.getElementById(__enabledControlArray[i]);
        }
        else {
            c = document.all[__enabledControlArray[i]];
        }
        if ((typeof(c) != "undefined") && (c != null) && (c.disabled == true)) {
            c.disabled = false;
            __disabledControlArray[disabledIndex++] = c;
        }
    }
    setTimeout("WebForm_ReDisableControls()", 0);
    return true;
}
function WebForm_ReDisableControls() {
    for (var i = 0; i < __disabledControlArray.length; i++) {
        __disabledControlArray[i].disabled = true;
    }
}
var __defaultFired = false;
function WebForm_FireDefaultButton(event, target) {
    if (!__defaultFired && event.keyCode == 13) {
        var defaultButton;
        if (__nonMSDOMBrowser) {
            defaultButton = document.getElementById(target);
        }
        else {
            defaultButton = document.all[target];
        }
        if (defaultButton.click != "undefined") {
            __defaultFired = true;
            defaultButton.click();
            event.cancelBubble = true;
            return false;
        }
    }
    return true;
}
function WebForm_GetScrollX() {
    if (__nonMSDOMBrowser) {
        return window.pageXOffset;
    }
    else {
        if (document.documentElement && document.documentElement.scrollLeft) {
            return document.documentElement.scrollLeft;
        }
        else if (document.body) {
            return document.body.scrollLeft;
        }
    }
    return 0;
}
function WebForm_GetScrollY() {
    if (__nonMSDOMBrowser) {
        return window.pageYOffset;
    }
    else {
        if (document.documentElement && document.documentElement.scrollTop) {
            return document.documentElement.scrollTop;
        }
        else if (document.body) {
            return document.body.scrollTop;
        }
    }
    return 0;
}
function WebForm_SaveScrollPositionSubmit() {
    if (__nonMSDOMBrowser) {
        theForm.elements['__SCROLLPOSITIONY'].value = window.pageYOffset;
        theForm.elements['__SCROLLPOSITIONX'].value = window.pageXOffset;
    }
    else {
        theForm.__SCROLLPOSITIONX.value = WebForm_GetScrollX();
        theForm.__SCROLLPOSITIONY.value = WebForm_GetScrollY();
    }
    if ((typeof(WebForm_ScrollPositionSubmit) != "undefined") && (WebForm_ScrollPositionSubmit != null)) {
        if (WebForm_ScrollPositionSubmit.apply) {
            return WebForm_ScrollPositionSubmit.apply(this);
        }
        else {
            return WebForm_ScrollPositionSubmit();
        }
    }
    return true;
}
function WebForm_SaveScrollPositionOnSubmit() {
    theForm.__SCROLLPOSITIONX.value = WebForm_GetScrollX();
    theForm.__SCROLLPOSITIONY.value = WebForm_GetScrollY();
    if ((typeof(WebForm_ScrollPositionOnSubmit) != "undefined") && (WebForm_ScrollPositionOnSubmit != null)) {
        if (WebForm_ScrollPositionOnSubmit.apply) {
            return WebForm_ScrollPositionOnSubmit.apply(this);
        }
        else {
            return WebForm_ScrollPositionOnSubmit();
        }
    }
    return true;
}
function WebForm_RestoreScrollPosition() {
    if (__nonMSDOMBrowser) {
        window.scrollTo(theForm.elements['__SCROLLPOSITIONX'].value, theForm.elements['__SCROLLPOSITIONY'].value);
    }
    else {
        window.scrollTo(theForm.__SCROLLPOSITIONX.value, theForm.__SCROLLPOSITIONY.value);
    }
    if ((typeof(WebForm_ScrollPositionLoad) != "undefined") && (WebForm_ScrollPositionLoad != null)) {
        if (WebForm_ScrollPositionLoad.apply) {
            return WebForm_ScrollPositionLoad.apply(this);
        }
        else {
            return WebForm_ScrollPositionLoad();
        }
    }
    return true;
}
function WebForm_TextBoxKeyHandler() {
    if (event.keyCode == 13) {
        if ((typeof(event.srcElement) != "undefined") && (event.srcElement != null)) {
            if (typeof(event.srcElement.onchange) != "undefined") {
                event.srcElement.onchange();
                return false;
            }
        }
    }
    return true;
}
