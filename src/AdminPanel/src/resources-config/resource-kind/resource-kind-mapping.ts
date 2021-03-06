import {autoinject} from "aurelia-dependency-injection";
import {AdvancedMapper, ArrayMapper} from "common/dto/mappers";
import {Workflow} from "workflows/workflow";
import {WorkflowRepository} from "workflows/workflow-repository";
import {AutoMapper} from "common/dto/auto-mapper";
import {maps} from "common/dto/decorators";
import {Metadata} from "../metadata/metadata";
import {TypeRegistry} from "common/dto/registry";
import {ResourceKind} from "./resource-kind";
import {Resource} from "../../resources/resource";

@autoinject
@maps('WorkflowId')
export class WorkflowIdMapper extends AdvancedMapper<Workflow> {
  constructor(private workflowRepository: WorkflowRepository, private autoMapper: AutoMapper<Workflow>) {
    super();
  }

  fromBackendProperty(key: string, dto: Object, resourceKind: Object): Promise<Workflow> {
    const workflowId = dto[key + 'Id'];
    return this.isEmpty(workflowId) ? Promise.resolve(undefined) : this.workflowRepository.get(workflowId);
  }

  toBackendProperty(key: string, resourceKind: ResourceKind, dto: Object): void {
    const workflow = resourceKind.workflow;
    dto[key + 'Id'] = this.isEmpty(workflow) ? undefined : workflow.id;
  }

  protected clone(workflow: Workflow): Workflow {
    return this.autoMapper.nullSafeClone(workflow);
  }
}

@autoinject
@maps('Metadata[]')
class MetadataListMapper extends ArrayMapper<Metadata> {
  constructor(typeRegistry: TypeRegistry) {
    super(typeRegistry.getMapperByType(Metadata.NAME), typeRegistry.getFactoryByType(Metadata.NAME));
  }
}

@autoinject
@maps('Resource[]')
export class ResourceMapper extends ArrayMapper<Resource> {
  constructor(typeRegistry: TypeRegistry) {
    super(typeRegistry.getMapperByType(Resource.NAME), typeRegistry.getFactoryByType(Resource.NAME));
  }
}
