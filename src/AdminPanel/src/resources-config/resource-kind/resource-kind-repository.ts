import {autoinject} from "aurelia-dependency-injection";
import {ResourceKind} from "./resource-kind";
import {cachedResponse, forSeconds} from "common/repository/cached-response";
import {ResourceClassApiRepository} from "common/repository/resource-class-api-repository";
import {EntitySerializer} from "common/dto/entity-serializer";
import {DeduplicatingHttpClient} from "common/http-client/deduplicating-http-client";

@autoinject
export class ResourceKindRepository extends ResourceClassApiRepository<ResourceKind> {
  constructor(httpClient: DeduplicatingHttpClient, entitySerializer: EntitySerializer) {
    super(httpClient, entitySerializer, ResourceKind, 'resource-kinds');
  }

  @cachedResponse(forSeconds(30))
  public getListByClass(resourceClass: string): Promise<ResourceKind[]> {
    return super.getListByClass(resourceClass);
  }

  @cachedResponse(forSeconds(30))
  public get(id: number|string, suppressError?: boolean): Promise<ResourceKind> {
    return super.get(id, suppressError);
  }

  @cachedResponse(forSeconds(30))
  public getList(): Promise<ResourceKind[]> {
    return super.getList();
  }

  public update(updatedResourceKind: ResourceKind): Promise<ResourceKind> {
    let backendRepresentation = this.toBackend(updatedResourceKind);
    return this.patch(updatedResourceKind, {
      label: updatedResourceKind.label,
      metadataList: backendRepresentation['metadataList'],
      displayStrategies: backendRepresentation['displayStrategies'],
    });
  }
}
