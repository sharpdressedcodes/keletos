import 'jest';
import Ajax from '../../../../src/Resources/js/components/ajax';

const mockData = { test: 'test' };

process.on('unhandledRejection', err => {

    throw err;

});

function createXHRMock(data = mockData) {

    const status = 200;
    const readyState = 4;
    const responseText = data ? JSON.stringify(data) : data;
    const open = jest.fn();
    const setRequestHeader = jest.fn();
    // We use *function* because we need to get *this*
    // from *new XmlHttpRequest()* call
    const send = jest.fn().mockImplementation(() => {
    // onload = this.onload.bind(this);
    // onerror = this.onerror.bind(this);
    // setRequestHeader = this.setRequestHeader.bind(this);
    });

    const xhrMockClass = function xhrMock() {

        return {
            open,
            send,
            status,
            setRequestHeader,
            responseText,
            readyState,
        };

    };

    window.XMLHttpRequest = jest.fn().mockImplementation(xhrMockClass);

}

describe('Ajax component', () => {

    let mockXHR = null;
    let ajax = null;

    it('should resolve a promise with a 200 status', () => {

        mockXHR = createXHRMock();
        ajax = new Ajax();

        const promise = ajax.send();

        ajax.xhr.onreadystatechange();

        return expect(promise).resolves.toStrictEqual(mockData);

    });

    it('should reject a promise when status != 200 and data is null', () => {

        // Force returning of null. This coupled with status != 200 will trigger the error.
        mockXHR = createXHRMock(null);
        ajax = new Ajax();

        const promise = ajax.send();

        // expect.assertions(1);

        ajax.xhr.status = 401;
        ajax.xhr.onreadystatechange();

        return expect(promise).rejects.toBeInstanceOf(Error);

    });

});
