class Element {

    static create(type, attributes = {}, content = null) {

        const el = document.createElement(type);

        Object.keys(attributes).forEach(attribute => {
            el.setAttribute(attribute, attributes[attribute]);
        });

        if (content) {
            if (typeof content === 'string') {
                const textEl = document.createTextNode(content);
                el.appendChild(textEl);
            } else if (typeof content === 'object') {
                el.appendChild(content);
            }
        }

        return el;

    }
}

export default Element;
