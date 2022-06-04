/**
 * Wordpress Dependencies
 */
import { render } from '@wordpress/element';
/**
 * Internal Dependencies
 */
import './style.scss';
import Admin from './app';

render(
    <Admin/>,
    document.getElementById('bca-root')
);