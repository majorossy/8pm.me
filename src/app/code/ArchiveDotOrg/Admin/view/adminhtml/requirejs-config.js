/**
 * RequireJS configuration for ArchiveDotOrg Admin module
 * Configures ApexCharts library path
 */
var config = {
    paths: {
        'apexcharts': 'ArchiveDotOrg_Admin/js/lib/apexcharts.min'
    },
    shim: {
        'apexcharts': {
            exports: 'ApexCharts'
        }
    }
};
