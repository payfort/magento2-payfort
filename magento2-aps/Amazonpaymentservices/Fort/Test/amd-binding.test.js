const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const renderers = path.join(__dirname, '..', 'view/frontend/web/js/view/payment/method-renderer');

for (const file of [
    'aps_benefit-method.js', 'aps_fort_cc-method.js', 'aps_fort_naps-method.js', 'aps_fort_sadad-method.js',
    'aps_fort_stc.js', 'aps_fort_tabby.js', 'aps_installment-method.js', 'aps_knet-method.js',
    'aps_omannet-method.js', 'aps_visacheckout_redirect.js'
]) {
    const dependency = 'post-redirect';
    test(`${file} binds ${dependency} to its AMD factory argument`, () => {
        const source = fs.readFileSync(path.join(renderers, file), 'utf8');
        let dependencies;
        let factory;
        const context = {
            define: (names, callback) => {
                dependencies = names;
                factory = callback;
            }
        };
        vm.runInNewContext(source, context, {filename: file});
        const index = dependencies.findIndex((name) => name.endsWith('/' + dependency));
        assert.notEqual(index, -1);
        const params = factory.toString().match(/^function\s*\(([^)]*)\)/)[1]
            .split(',').map((param) => param.trim());
        assert.equal(params[index], 'postRedirect');
        assert.match(source, /postRedirect\(/);
    });
}

test('aps_fort_valu-method.js binds underscore to its _ factory argument', () => {
    const file = 'aps_fort_valu-method.js';
    const source = fs.readFileSync(path.join(renderers, file), 'utf8');
    let dependencies;
    let factory;
    vm.runInNewContext(source, {define: (names, callback) => { dependencies = names; factory = callback; }}, {filename: file});
    const params = factory.toString().match(/^function\s*\(([^)]*)\)/)[1].split(',').map((param) => param.trim());
    assert.equal(params[dependencies.indexOf('underscore')], '_');
    assert.equal(params[dependencies.indexOf('jquery')], '$');
});
