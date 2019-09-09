import Ajax from './ajax';
import Element from './element';
import 'babel-polyfill';

class Form {

    static defaultResponse = {
        error: null,
        data: null,
    };

    static defaults = {
        useAjax: false,
        insertFormName: false,
        findValidationElements: form => [],
        csrfName: 'token',
        submitSelector: '[type=submit]',
        onBeforeSubmit: valid => true,
        onSubmitSuccess: response => {},
        onSubmitError: response => {},
        onAfterSubmit: () => {},
        useMessages: false,
        messagesSelector: '.message-container',
        hasMessageContainer: false
    };

    constructor(selectorOrElement, options) {

        this.options = Object.assign({}, Form.defaults, options);
        this.form = typeof selectorOrElement === 'string' ? document.querySelector(selectorOrElement) : selectorOrElement;
        this.elements = this.options.findValidationElements.call(this, this.form);
        this.submitButton = this.form.querySelector(this.options.submitSelector);
        this.csrfElement = this.form.querySelector(`input[name=${this.options.csrfName}]`);

        if (this.options.useMessages) {

            if (this.options.hasMessageContainer) {

                this.messageContainer = this.form.querySelector(this.options.messagesSelector) || document.querySelector(this.options.messagesSelector);
                this.messageList = this.messageContainer ? this.messageContainer.querySelector('ul') : null;

            } else {

                this.messageContainer = null;
                this.messageList = this.form.querySelector(this.options.messagesSelector) || document.querySelector(this.options.messagesSelector);

            }
        }

        if (this.options.insertFormName && this.form.children.length) {

            const hidden = Element.create('input', {
                type: 'hidden',
                name: 'form-name',
                value: this.getFormName()
            });

            this.form.insertAdjacentElement('afterbegin', hidden);

        }

        this.form.addEventListener('submit', this.onFormSubmit, false);

    }

    getFormName() {

        let name = null;

        if (this.form.id) {

            name = this.form.id;

        } else if (this.form.className.includes('form')) {

            const classes = this.form.className.split(' ');

            classes.every(className => {

                if (className.includes('form') && className !== 'form') {

                    name = className;
                    return false;

                }
                return true;

            });

        }

        if (!name) {

            const forms = Array.from(document.getElementsByTagName('form'));

            forms.every((form, index) => {

                if (form === this.form) {

                    name = `form-${index}`;
                    return false;

                }
                return true;

            });

        }

        return name;

    }

    onFormSubmit = async event => {

        let valid = true;
        let lastElement = null;

        this.elements.every(element => {

            valid = element.validity.valid;

            if (!valid) {
                lastElement = element;
            }

            // console.log(`element ${element.getAttribute('name')} is valid=${valid}`);
            return valid;

        });

        if (this.options.onBeforeSubmit({ elements: this.elements, lastElement, valid })) {

            this.clearMessages();

            if (this.options.useAjax) {

                event.preventDefault();
                // Disable button
                this.submitButton.setAttribute('disabled', 'disabled');

                const method = (this.form.getAttribute('method') || 'get').toLowerCase();
                const url = this.form.getAttribute('action') || window.location.href;
                const formData = new FormData(this.form);
                const params = {};
                const ajax = new Ajax();

                // eslint-disable-next-line no-restricted-syntax
                for (const pair of formData.entries()) {

                    params[pair[0]] = pair[1];

                }

                const options = {
                    url,
                    method,
                    params,
                };

                let data = null;

                try {

                    data = await ajax.send(options);
                    data = { data };

                } catch (error) {

                    data = { error, data };

                }

                // Enable button
                this.submitButton.removeAttribute('disabled');
                this.updateCsrf(data.data.csrf);

                if (data.error /*|| !data.success*/) {
                    this.options.onSubmitError(Object.assign({}, Form.defaultResponse, data));
                } else {
                    this.options.onSubmitSuccess(Object.assign({}, Form.defaultResponse, data));
                }

                this.options.onAfterSubmit();

                return;

            }

            // No ajax
            if (!valid) {

                event.preventDefault();

            }

        }

    };

    updateCsrf = csrf => {

        if (this.csrfElement) {
            this.csrfElement.value = csrf;
        }

    };

    clearMessages = () => {

        if (this.options.useMessages) {

            let el = this.messageContainer || this.messageList;

            if (el) {

                if (!el.className.includes('hidden')) {

                    el.className += ' hidden';

                }
            }

            if (this.messageList) {
                while (this.messageList.firstChild) {

                    this.messageList.removeChild(this.messageList.firstChild);

                }
            }

        }

    };

}

export default Form;
