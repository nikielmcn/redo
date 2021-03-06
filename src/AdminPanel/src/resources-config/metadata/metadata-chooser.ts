import {autoinject} from "aurelia-dependency-injection";
import {I18N} from "aurelia-i18n";
import {bindable, customElement} from "aurelia-templating";
import {EntityChooser} from "common/components/entity-chooser/entity-chooser";
import {MetadataRepository} from "./metadata-repository";
import {Metadata} from "./metadata";

@customElement('metadata-chooser')
@autoinject
export class MetadataChooser extends EntityChooser {
  @bindable resourceClass: string | string[];
  @bindable control: string | string[];
  @bindable additionalMetadataIds: number[] = [];
  private loadingMetadataList = false;
  private reloadMetadataList = false;

  constructor(private metadataRepository: MetadataRepository, i18n: I18N, element: Element) {
    super(i18n, element);
  }

  attached() {
    super.attached();
    this.loadMetadataList();
  }

  resourceClassChanged() {
    if (this.entities) {
      this.loadMetadataList();
    }
  }

  controlChanged() {
    if (this.entities) {
      this.loadMetadataList();
    }
  }

  private loadMetadataList() {
    if (this.loadingMetadataList) {
      this.reloadMetadataList = true;
    } else {
      this.loadingMetadataList = true;
      const controlMetadata = this.metadataRepository.getListQuery()
        .filterByResourceClasses(this.resourceClass)
        .filterByControls(this.control)
        .onlyTopLevel()
        .get();
      const additionalMetadata = this.additionalMetadataIds.length > 0
        ? this.metadataRepository.getListQuery()
          .filterByIds(this.additionalMetadataIds)
          .get()
        : [];
      Promise.all([additionalMetadata, controlMetadata])
        .then(metadataListList => ([] as Metadata[]).concat(...metadataListList))
        .then(metadataList => {
          this.loadingMetadataList = false;
          if (this.reloadMetadataList) {
            this.reloadMetadataList = false;
            this.loadMetadataList();
          } else {
            this.entities = metadataList;
          }
        })
        .finally(() => this.loadingMetadataList = false);
    }
  }

  get isFetchingOptions() {
    return this.loadingMetadataList;
  }
}
