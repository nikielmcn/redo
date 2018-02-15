import {bindable, ComponentAttached} from "aurelia-templating";
import {computedFrom} from "aurelia-binding";
import {Workflow, WorkflowPlace, RestrictingMetadataIdMap} from "../../workflow";
import {Metadata} from "resources-config/metadata/metadata";
import {MetadataRepository} from "resources-config/metadata/metadata-repository";
import {autoinject} from "aurelia-dependency-injection";
import {WorkflowPlaceSorter} from "./workflow-place-sorter";
import {booleanAttribute} from "common/components/boolean-attribute";
import {twoWay} from "common/components/binding-mode";
import {deepCopy} from "common/utils/object-utils";
import "sticky-table-headers";

@autoinject
export class Excel implements ComponentAttached {
  @bindable(twoWay) workflow: Workflow;
  @bindable @booleanAttribute editable: boolean = false;
  @bindable resourceClass: string;

  metadataList: Metadata[];

  constructor(private metadataRepository: MetadataRepository, private workflowPlaceSorter: WorkflowPlaceSorter) {
  }

  async attached() {
    $('.highlight-col-row-on-hover').stickyTableHeaders();
    this.metadataList = await this.metadataRepository.getListByClass(this.resourceClass);
  }

  @computedFrom('workflow.places')
  get orderedPlaces(): WorkflowPlace[] {
    return this.workflowPlaceSorter.getOrderedPlaces(this.workflow);
  }

  checkboxChanged(metadata: Metadata, changedPlace: WorkflowPlace): void {
    const newState = changedPlace.restrictingMetadataIds[metadata.id];
    const changedPlaceIndex = this.orderedPlaces.indexOf(changedPlace);
    const placesToUpdate = this.orderedPlaces.slice(changedPlaceIndex + 1);
    for (const place of placesToUpdate) {
      const map: RestrictingMetadataIdMap = deepCopy(place.restrictingMetadataIds);
      map[metadata.id] = newState;
      place.restrictingMetadataIds = map;
    }
  }
}
