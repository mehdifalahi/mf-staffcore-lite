(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var root = document.querySelector('[data-staffcore-module="payroll-payslip"]');
    if (!root) {
        return;
    }

    var printBtn = root.querySelector('[data-action="print-payslip"]');
    var printArea = root.matches('[data-role="print-area"]')
        ? root
        : root.querySelector('[data-role="print-area"]');

    if (!printBtn || !printArea) {
        return;
    }

    function openPrint() {
        var $area = $(printArea);

        if (typeof $area.printThis === 'function') {
            $area.printThis({
                importCSS: true,
                importStyle: true,
                loadCSS: '',
                printContainer: true,
                pageTitle: document.title || 'Payslip',
                removeScripts: false,
                copyTagClasses: true,
                copyTagStyles: true,
            });

            return;
        }

        window.print();
    }

    printBtn.addEventListener('click', function () {
        openPrint();
    });
})(window.jQuery);
