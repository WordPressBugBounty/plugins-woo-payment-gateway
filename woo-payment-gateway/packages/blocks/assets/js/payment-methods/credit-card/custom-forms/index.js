import {registerCustomForm} from "../custom-form/registry";
import BootstrapForm from './bootstrap';
import SimpleForm from './simple';

registerCustomForm({
    id: 'bootstrap_form',
    content: <BootstrapForm/>,
    breakpoint: 425,
    fields: ['number', 'expirationDate', 'cvv'],
    className: 'bootstrap-md'
});
registerCustomForm({
    id: 'simple_form',
    content: <SimpleForm/>,
    breakpoint: 425,
    fields: ['number', 'expirationDate', 'cvv'],
    className: 'simple-form-md'
})