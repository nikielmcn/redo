import {ResourceKind} from "resources-config/resource-kind/resource-kind";
import {ValidationRules} from "aurelia-validation";
import {TransitionBlockReason, WorkflowPlace, WorkflowTransition} from "workflows/workflow";
import {Entity} from "common/entity/entity";
import {MetadataConstraintsSatisfiedValidationRule} from "common/validation/rules/metadata-constraints-satisfied";
import {copy, map, mappedWith, arrayOf, dictOf} from "common/dto/decorators";
import {ResourceKindIdMapper, ResourceMapper} from "./resource-mapping";

@mappedWith(ResourceMapper)
export class Resource extends Entity {
  static NAME = 'Resource';

  @map id: number;
  @map(ResourceKindIdMapper) kind: ResourceKind;
  @map(arrayOf(WorkflowPlace)) currentPlaces: WorkflowPlace[];
  @map(arrayOf(WorkflowTransition)) availableTransitions: WorkflowTransition[] = [];
  @map blockedTransitions: StringMap<TransitionBlockReason> = {};
  @map(dictOf(arrayOf(WorkflowTransition))) transitionAssigneeMetadata: NumberMap<WorkflowTransition[]> = {};
  @copy contents: StringArrayMap = {};
  @map resourceClass: string;

  public canApplyTransition(transition: WorkflowTransition) {
    return this.blockedTransitions[transition.id] == undefined;
  }

  public filterUndefinedValues() {
    for (let metadataId in this.contents) {
      if (this.contents[metadataId].length > 0) {
        this.contents[metadataId] = this.contents[metadataId].filter(item => item !== undefined);
      }
    }
  }
}

export function registerResourceValidationRules() {
  ValidationRules
    .ensure('kind').displayName("Resource kind").required()
    .ensure('contents').displayName('Contents')
    .satisfies(contents => Object.keys(contents).filter(metadataId => contents[metadataId].length > 0).length > 0)
    .withMessageKey('atLeastOneMetadataRequired')
    .satisfiesRule(MetadataConstraintsSatisfiedValidationRule.NAME)
    .withMessageKey('metadataConstraintsNotSatisfied')
    .on(Resource);
}
