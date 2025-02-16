/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import '../css/frontend.css';
// Need jQuery? Install it with "yarn add jquery", then uncomment to import it.
import $ from 'jquery';
import Popper from 'popper.js';
import {jarallax, jarallaxElement, jarallaxVideo} from 'jarallax';

global.$ = global.jQuery = $;

global.Popper = Popper;
import('bootstrap');
import('./mdb');
import ('jquery-lazy');

jarallaxVideo();
jarallaxElement();
jarallax(document.querySelectorAll('.jarallax'), {
    speed: 0.2
});
$(function () {
    $(function () {
        $(".scroller").on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: $($(this).attr('href')).offset().top}, 500, 'linear');
        });
    });
});
$(document).on('click', '.loadContent', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    $('#loadContentModal').load(url, function () {
        $('#loadContentModal ').modal('show');
    });

});

$(document).ready(function () {
    $('.nav-tabs').scrollingTabs({
        bootstrapVersion: 4,
        cssClassLeftArrow: 'fa fa-chevron-left',
        cssClassRightArrow: 'fa fa-chevron-right',
        disableScrollArrowsOnFullyScrolled: true
    });

});

$(window).on('load', function () {

    $(function () {
        $('.lazy').show().Lazy({
            // your configuration goes here
            scrollDirection: 'vertical',
            effect: 'fadeIn',
            visibleOnly: true,
            onError: function (element) {
                console.log('error loading ' + element.data('src'));
            }
        });
    });


});
$(document).on('click', '.loadInTarget', function (e) {
    e.preventDefault();
    var ele = $(this);
    $(ele.attr('data-wrapper')).load(ele.attr('href') + ' ' + ele.attr('data-target'), function () {

        Waves.attach('.waves-light', ['waves-effect']);
    });

    window.history.pushState('test', "test", ele.attr('href'));

})
