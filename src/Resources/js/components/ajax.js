class Ajax {

    static defaults = {
        url: window.location.href,
        method: 'GET',
        params: {},
        headers: {},
    };

    static onSuccess = () => {};

    static onError = () => {};

    constructor() {

        this.xhr = null;

    }

    get(options) {

        this.send(Object.assign({}, options, { method: 'GET' }));

    }

    post(options) {

        this.send(Object.assign({}, options, { method: 'POST' }));

    }

    send(options) {

        return new Promise((resolve, reject) => {

            const xhr = new XMLHttpRequest();
            const opts = Object.assign({}, Ajax.defaults, options);

            this.xhr = xhr;
            xhr.open(opts.method, opts.url, true);

            xhr.onreadystatechange = () => {

                if (xhr.readyState === 4) {

                    if (xhr.status === 200 || xhr.responseText !== null) {

                        try {

                            resolve(JSON.parse(xhr.responseText));

                        } catch (e) {

                            reject(e);

                        }

                    } else {

                        const e = new Error(`200 status not received, got ${xhr.status} instead.`);
                        reject(e);

                    }

                }

            };

            let body = '';

            if (opts.params) {

                body = Object.keys(opts.params)
                    .map(name => `${name}=${encodeURIComponent(opts.params[name])}`)
                    .join('&');

                if (body.length) {

                    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                }

            }

            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            Object.keys(opts.headers).forEach(key => {

                xhr.setRequestHeader(key, opts.headers[key]);

            });

            xhr.send(body);

        });

    }

}

export default Ajax;
