/**
 * Wordpress Dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import { createElement, render } from '@wordpress/element';
/**
 * Internal Dependencies
 */
import './style.scss';
import Admin from './app';


function fetchDuplicates() {
    return apiFetch({ path: '/bca/delete-duplicates/v1/search' }).then((results) => {
        // console.log( results );
        return results.length;
    });
}
function updateProgressBar(value) {
    return new Promise(function (resolve, reject) {
        let progressBars = document.querySelectorAll('progress');
        progressBars.forEach(progressBar => {
            progressBar.setAttribute('value', value);
        });
        resolve();
        reject();
    });

}
function deleteDuplicates(current = 0, limit = 5, total = 0) {

    // total:64 per_page:5 current:0 end:64/5=12.6(13)
    let end = Math.ceil(total / limit);

    if (total == 0) return;

    if (current > end) {
        return;
    }

    if (current < 1) {
        current = 1;
    } else if (current > end) {
        current = end;
    }

    let query = new URLSearchParams({
        current,
        limit,
        total
    });

    apiFetch({
        path: '/bca/delete-duplicates/v1/delete?' + query.toString(),
    }).then((results) => {
        console.log(results);
        if (results.error) {
            return;
        }
        let { current, limit, total } = results;
        let currentValue = (current / end) * 100;
        let next = current + 1;

        updateProgressBar(currentValue).then(() => {
            setTimeout(() => {
                deleteDuplicates(next, limit, total);
            }, 50);
        });

    });

}

async function initDeleteDuplicates() {

    let duplicates = await fetchDuplicates();

    if (duplicates.length == 0) {
        return;
    }

    deleteDuplicates(0, 1, duplicates);

}

// domReady(function () {
//     //do something after DOM loads.
//     document.querySelector('#start-deletion').addEventListener('click', initDeleteDuplicates);
// });

render(
    <Admin/>,
    document.getElementById('bca-root')
);