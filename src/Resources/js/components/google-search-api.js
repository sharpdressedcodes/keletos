import Form from './form';

class GoogleSearchApi {

    static defaults = {
        useAjax: false,
        useMessages: false,
        hasMessageContainer: false,
        messagesSelector: '',
        formSelector: '',
        findFormValidationElements: el => []
    };

    constructor(options) {

        this.options = Object.assign({}, GoogleSearchApi.defaults, options);

        this.form = new Form(this.options.formSelector, {
            useAjax: this.options.useAjax,
            onSubmitSuccess: this.onSubmitSuccess,
            onSubmitError: this.onSubmitError,
            onAfterSubmit: this.onAfterSubmit,
            useMessages: this.options.useMessages,
            hasMessageContainer: this.options.hasMessageContainer,
            messagesSelector: this.options.messagesSelector,
            findValidationElements: this.options.findFormValidationElements
        });

    }

    onSubmitSuccess = response => {
        this.form.messageList.insertAdjacentHTML('beforeend', `<li>${response.data.appearances.join(', ')}</li>`);
    };

    onSubmitError = response => {

        let errors = [];

        if (response.error) {

            errors.push(response.error);

        } else {

            errors = response.data.errors;

        }

        errors.forEach(error => {

            // Ignore the keys for now.
            const str = typeof error !== 'string' ? Object.values(error)[0] : error;

            // Using insertAdjacentHTML so that it also renders html inside the message
            this.form.messageList.insertAdjacentHTML('beforeend', `<li>${str}</li>`);

        });

    };

    onAfterSubmit = () => {
        this.form.messageContainer.className = this.form.messageContainer.className.replace('hidden', '').trim();
    };

}

export default GoogleSearchApi;
