import {autoinject} from "aurelia-dependency-injection";
import {bindable, ComponentAttached} from "aurelia-templating";
import {booleanAttribute} from "common/components/boolean-attribute";
import {ResourceKind} from "resources-config/resource-kind/resource-kind";
import {ResourceKindRepository} from "resources-config/resource-kind/resource-kind-repository";
import {oneTime, twoWay} from "../../binding-mode";

@autoinject
export class ResourceKindChooser implements ComponentAttached {
  @bindable(twoWay) value: ResourceKind | ResourceKind[];
  @bindable(oneTime) multiSelect: boolean;
  @bindable resourceClass: string;
  @bindable disabled: boolean;
  @bindable hideClearButton: boolean = false;
  @bindable filter: (resourceKind: ResourceKind) => boolean = () => true;
  @bindable @booleanAttribute useComputedWidth: boolean;
  resourceKinds: ResourceKind[];

  constructor(private resourceKindRepository: ResourceKindRepository) {
  }

  attached() {
    const query = this.resourceKindRepository.getListQuery();
    if (this.resourceClass) {
      query.filterByResourceClasses(this.resourceClass);
    }
    query.get().then(resourceKinds => {
      this.resourceKinds = resourceKinds.filter(this.filter);
    });
  }
}
