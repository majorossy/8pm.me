var config = {  
    paths: {
            'amplitude':'ArchiveDotOrg_Player/js/amplitude',
             'slick':'ArchiveDotOrg_Player/js/slick'
    } ,
    shim: {
        'amplitude': {
            'deps': ['jquery']
        },
         'slick': {
            'deps': ['jquery']
        }       
    },
    mixins: {
        'MSP_ReCaptcha/js/reCaptcha': {
            'ArchiveDotOrg_Player/js/reCaptcha-mixin': true
        }
    }
}