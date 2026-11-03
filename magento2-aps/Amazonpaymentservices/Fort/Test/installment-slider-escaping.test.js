/**
 * Regression test for the installment DOM XSS: every installment slider builder
 * must escape upstream plan values before the markup reaches jQuery .html().
 *
 * Each `sliderText += ...` statement is evaluated as written in the caller.
 * Set UNDERSCORE_PATH to a real underscore.js to use its _.escape; otherwise a
 * copy of underscore's documented escape map is used.
 */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const {test} = require('node:test');

const renderers = path.join(__dirname, '..', 'view/frontend/web/js/view/payment/method-renderer');
const hostileValue = '"><img src=x onerror=alert(1)>';
// file -> [loop variable, upstream fields used by its slider builders]
const callers = {
    'aps_installment_hosted-method.js': ['values', ['number_of_installment', 'issuer_code', 'plan_code', 'amountPerMonth', 'interest', 'currency_code']],
    'aps_fort_cc_merchant_page2-method.js': ['values', ['number_of_installment', 'issuer_code', 'plan_code', 'amountPerMonth', 'interest', 'currency_code']],
    'aps_fort_valu-method.js': ['value', ['number_of_installments', 'fees_amount', 'amount_per_month', 'TENURE', 'InterestRate', 'EMI']]
};

function loadUnderscore() {
    if (process.env.UNDERSCORE_PATH) {
        const sandbox = {};
        sandbox.window = sandbox;
        sandbox.self = sandbox;
        vm.runInNewContext(fs.readFileSync(process.env.UNDERSCORE_PATH, 'utf8'), sandbox);
        return sandbox._;
    }
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;', '`': '&#x60;'};
    return {escape: (v) => (v == null ? '' : String(v)).replace(/[&<>"'`]/g, (c) => map[c])};
}

function sliderStatements(file, variable) {
    const source = fs.readFileSync(path.join(renderers, file), 'utf8');
    return source.split('\n').map((line) => line.trim()).filter((line) => line.startsWith('sliderText +=') && line.includes(variable + '.'));
}

const hostile = hostileValue;

for (const [file, [variable, fields]] of Object.entries(callers)) {
    test(`${file}: plan slider escapes every upstream value`, () => {
        const statements = sliderStatements(file, variable);
        assert.equal(statements.length, 2, 'expected both slider builders');
        const _ = loadUnderscore();
        assert.equal(typeof _.escape, 'function');
        for (const statement of statements) {
            const context = {sliderText: '', _, $: {mage: {__: (s) => s}}};
            context[variable] = Object.fromEntries(fields.map((field) => [field, hostile]));
            vm.runInNewContext(statement, context);
            const html = context.sliderText;
            assert.ok(!html.includes('<img'), html);
            assert.ok(!html.includes(hostile), html);
            assert.ok(html.includes('&lt;img'), 'escaped text should still be shown');
        }
    });
}
