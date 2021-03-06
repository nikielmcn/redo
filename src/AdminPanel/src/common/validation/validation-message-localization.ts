import {Aurelia} from "aurelia-framework";
import {I18N} from "aurelia-i18n";
import {ValidationMessageProvider} from "aurelia-validation";

function ruleNameToKey(ruleName: string): string {
  const suffixToStrip = 'ValidationRule';
  const ruleNameRoot = (ruleName.endsWith(suffixToStrip))
    ? ruleName.substr(0, ruleName.length - suffixToStrip.length)
    : ruleName;
  return `validation::${ruleNameRoot}`;
}

// Based on http://aurelia.io/docs/plugins/validation#integrating-with-aurelia-i18n
export function installValidationMessageLocalization(aurelia: Aurelia) {
  const i18n = aurelia.container.get(I18N);

  ValidationMessageProvider.prototype.getMessage = function (ruleName) {
    const key = ruleNameToKey(ruleName);
    let translation = i18n.tr(key);
    return this.parser.parse(translation);
  };

  ValidationMessageProvider.prototype.getDisplayName = function (propertyName, displayName) {
    return i18n.tr(displayName || propertyName);
  };
}
