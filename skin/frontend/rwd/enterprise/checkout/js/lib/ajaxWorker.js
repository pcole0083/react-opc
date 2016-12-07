importScripts('fetch.js');

const checkStatus = function(response) {
    if (response.status >= 200 && response.status < 300) {
        return response;
    } else {
        var error = new Error(response.statusText)
        error.response = response;
        throw error;
    }
};

const ajaxFetch = function(url, options, successCallback) {
    var myHeaders = new Headers({
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
    });
    var optz = Object.assign({ credentials: 'include', headers: myHeaders }, options);

    if(optz.method === 'GET' && !!optz.body){
        delete optz.body;
    }

    const parseJSON = function(response) {
        if(!!response.json){
            successCallback(response.json());
        }
        return response;
    };

    var myRequest = new Request(url, optz);

    fetch(myRequest)
        .then(checkStatus)
        .then(parseJSON)
        .catch(function(error) {
            console.log('request failed', error);
        });
};

const ajaxData = (function() {

    /**
     * [ajaxData - This sends an XHR with custom params]
     * Modular pattern to make this work with node.js module pattern.
     * @param  {[object]} options [parameter object with settings for the XHR]
     */

    /**
     * defaults - default Ajax settings.
     * @type {Object}
     */
    var defaults = {
        method: 'GET',
        dataType: 'json'
    };

    /**
     * logback - default callback function for Ajax calls
     * @param  {Object / HTML / String} data - data returned via Ajax
     */
    var logback = function(promise) {
        if(!!promise){
            var cast = Promise.resolve(promise);
            cast.then(function(data){
                console.log(data);
                postMessage(JSON.stringify(data));
                close();
            });
        }
    };

    /**
     * failback - callback that runs on fail Ajax call.
     * @param  {String} textStatus - string description of error.
     * @param  {Object} errorThrown - Object holding the error
     */
    var failback = function(textStatus, errorThrown) {
        console.error(textStatus);
        console.error(errorThrown);
        postMessage({ 'error': true, 'message': textStatus, 'errorThrown': errorThrown });
        close();
    };

    /**
     * genericAjax : factory function for creating the get and set methods.
     * @param  {String} type GET or POST
     * @return {Function} generated function
     */
    var genericAjax = function(type) {
        return function(options, fn, failFn) {
            var opts = Object.assign({}, defaults, options);
            opts.method = !!type ? type : opts.method;

            fn = !!fn && fn instanceof Function ? fn : logback;
            failFn = !!failFn && failFn instanceof Function ? failFn : failback;

            if (!!opts.url) {
                var url = opts.url;
                delete opts.url;
                ajaxFetch(url, opts, fn);
            } else {
                console.warn('No URL supplied for fetch call: ');
                console.warn(opts);
            }
        };
    };

    /**
     * Ajax GET
     */
    var _get = (function() {
        return genericAjax('GET');
    })();

    /**
     * Ajax POST (set)
     */
    var _set = (function() {
        return genericAjax('POST');
    })();

    return {
        get: _get,
        set: _set
    };
}());

onmessage = function(oEvent) {
    if (oEvent.data instanceof Object && oEvent.data.hasOwnProperty("type") && oEvent.data.hasOwnProperty("xhrdata")) {
        var type = oEvent.data.type;
        var xhrdata = oEvent.data.xhrdata;

        // let xhrFormData = new FormData();
        let xhrFormData = [];

        Object.keys(xhrdata.body).map(function(key){
            let dataComponent = [encodeURIComponent(key), encodeURIComponent(xhrdata.body[key])].join('=');
            if(key.indexOf('street') > -1 ){
                dataComponent.replace('=', '%3D');
            }
            xhrFormData.push(dataComponent);
            return key;
        });

        let stringData = xhrFormData.join('&');

        xhrdata.body = stringData;

        ajaxData[type](xhrdata);
    }
}
