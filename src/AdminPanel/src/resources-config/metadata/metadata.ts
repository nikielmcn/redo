import {ValidationRules} from "aurelia-validation";
import {RequiredInAllLanguagesValidationRule} from "common/validation/rules/required-in-all-languages";
import {ResourceKind} from "../resource-kind/resource-kind";
import {MetadataRepository} from "./metadata-repository";
import {deepCopy} from "common/utils/object-utils";
import {Entity} from "common/entity/entity";
import {SystemResourceKinds} from "../resource-kind/system-resource-kinds";
import {arraysEqual} from "common/utils/array-utils";
import {computedFrom} from "aurelia-binding";

export interface MultilingualText extends StringStringMap {
}

export class Metadata extends Entity {
  id: number;
  name: string = '';
  label: MultilingualText = {};
  placeholder: MultilingualText = {};
  description: MultilingualText = {};
  control: string = 'text';
  parentId: number;
  baseId: number;
  constraints: MetadataConstraints = new MetadataConstraints();
  shownInBrief: boolean;
  resourceClass: string;

  @computedFrom('control', 'constraints.resourceKind')
  get canDetermineAssignees(): boolean {
    return this.control == 'relationship'
      && arraysEqual(this.constraints.resourceKind, [SystemResourceKinds.USER_ID]);
  }

  clearInheritedValues(metadataRepository: MetadataRepository, baseId?: number): Promise<Metadata> {
    const baseMetadata: Promise<Metadata> = (baseId != undefined)
      ? metadataRepository.get(baseId)
      : metadataRepository.getBase(this);
    return baseMetadata.then(base => {
      for (let overridableField of ['label', 'placeholder', 'description']) {
        for (let languageCode in this[overridableField]) {
          if (this[overridableField][languageCode] == base[overridableField][languageCode]) {
            this[overridableField][languageCode] = '';
          }
        }
      }
      return this;
    });
  }

  static clone(metadata: Object): Metadata {
    let cloned = deepCopy(metadata);
    cloned.constraints = $.extend(new MetadataConstraints(), metadata['constraints']);
    return $.extend(new Metadata(), cloned);
  }

  static createFromBase(baseMetadata: Metadata): Metadata {
    let metadata = Metadata.clone(baseMetadata);
    metadata.baseId = baseMetadata.id;
    return metadata;
  }
}

export class MetadataConstraints {
  resourceKind: ResourceKind[]|number[] = [];
}

export function registerMetadataValidationRules() {
  ValidationRules
    .ensure('label').displayName('Label').satisfiesRule(RequiredInAllLanguagesValidationRule.NAME)
    .ensure('control').displayName('Control').required()
    .ensure('name').displayName('Name').required()
    .on(Metadata);
}
