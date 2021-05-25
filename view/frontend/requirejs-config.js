var config = {
    map: {
        '*': {
            "payfort_fort": 'Payfort_Fort/js/payfort_fort'
        }
    },
    shim: {
        //dependency third-party lib
        "payfort_fort": {
             deps: [
                'jquery' //dependency jquery will load first
            ]
        }
    }
};