import {Resource} from "../../../resources/resource";
import * as Handlebars from "handlebars";
import {ResourceKind} from "../resource-kind";
import {autoinject} from "aurelia-dependency-injection";

@autoinject
export class ResourceDisplayStrategyEvaluator {
  private defaultTemplate: HandlebarsTemplateDelegate;

  constructor() {
    this.defaultTemplate = Handlebars.compile('#{{id}}');
  }

  public getDisplayValue(resource: Resource, strategyId: string): string {
    if (!resource) {
      return '';
    }
    const template = this.compileTemplateOrDefault(resource.kind.displayStrategies[strategyId]);
    return template(new ResourceDisplayStrategyTemplateData(resource));
  }

  public compileTemplate(template: string) {
    const compiled = Handlebars.compile(template);
    compiled({}); // detects possible template runtime errors
    return compiled;
  }

  public compileTemplateOrDefault(template: string): HandlebarsTemplateDelegate {
    if (!template) {
      return this.defaultTemplate;
    } else {
      try {
        return this.compileTemplate(template);
      } catch (error) {
        return this.defaultTemplate;
      }
    }
  }
}

class ResourceDisplayStrategyTemplateData {
  private id: number;
  private kind: ResourceKind;

  constructor(resource: Resource) {
    this.id = resource.id;
    this.kind = resource.kind;
    for (let metadataId in resource.contents) {
      this["m" + metadataId] = resource.contents[metadataId];
    }
  }
}

// missing helper called when unknown variable has been used: https://stackoverflow.com/a/25631909/878514
Handlebars.registerHelper('helperMissing', function (/* [args, ] options */) {
  const options = arguments[arguments.length - 1];
  return '{{' + options.name + '}}';
});
