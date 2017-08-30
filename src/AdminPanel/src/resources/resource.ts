import {ResourceKind} from "resources-config/resource-kind/resource-kind";
import {ValidationRules} from "aurelia-validation";
import {TransitionBlockReason, WorkflowPlace, WorkflowTransition} from "workflows/workflow";
import {Entity} from "common/entity/entity";
import {MetadataConstraintsSatisfiedValidationRule} from "common/validation/rules/metadata-constraints-satisfied";
import {copy, map, mappedWith} from "common/dto/decorators";
import {ResourceKindIdMapper, ResourceMapper} from "./resource-mapping";

@mappedWith(ResourceMapper)
export class Resource extends Entity {
  static NAME = 'Resource';

  @map id: number;
  @map(ResourceKindIdMapper) kind: ResourceKind;
  @map('WorkflowPlace[]') currentPlaces: WorkflowPlace[];
  @map('WorkflowTransition[]') availableTransitions: WorkflowTransition[] = [];
  @map blockedTransitions: StringMap<TransitionBlockReason> = {};
  @map('{WorkflowTransition[]}') transitionAssigneeMetadata: NumberMap<WorkflowTransition[]> = {};
  @copy contents: StringArrayMap = {};
  @map resourceClass: string;

  public canApplyTransition(transition: WorkflowTransition) {
    return this.blockedTransitions[transition.id] == undefined;
  }
}

export function registerResourceValidationRules() {
  // @codingStandardsIgnoreStart
  // @formatter:off because indentation makes config structure way clearer
  ValidationRules
    .ensure('kind').displayName("Resource kind").required()
    .ensure('contents').displayName('Contents')
      .satisfies(contents => Object.keys(contents).filter(metadataId => contents[metadataId].length > 0).length > 0)
        .withMessageKey('atLeastOneMetadataRequired')
      .satisfiesRule(MetadataConstraintsSatisfiedValidationRule.NAME)
        .withMessageKey('metadataConstraintsNotSatisfied')
    .on(Resource);
  // @formatter:on
  // @codingStandardsIgnoreEnd
}
