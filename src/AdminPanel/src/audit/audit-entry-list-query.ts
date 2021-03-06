import {AbstractListQuery} from "../resources/abstract-list-query";
import {AuditEntry} from "./audit-entry";
import {DeduplicatingHttpClient} from "../common/http-client/deduplicating-http-client";
import {EntitySerializer} from "../common/dto/entity-serializer";

export class AuditEntryListQuery extends AbstractListQuery<AuditEntry> {
  constructor(httpClient: DeduplicatingHttpClient, endpoint: string, entitySerializer: EntitySerializer) {
    super(httpClient, endpoint, entitySerializer, 'AuditEntry[]');
  }

  public filterByCommandNames(commandNames: string[]): this {
    this.params.commandNames = commandNames;
    return this;
  }

  public filterByResourceContents(resourceContents: NumberMap<string>): this {
    this.params.resourceContents = JSON.stringify(resourceContents);
    return this;
  }

  public filterByResourceId(resourceId: number): this {
    this.params.resourceId = resourceId;
    return this;
  }

  public filterByDateFrom(dateFrom: string): this {
    this.params.dateFrom = dateFrom;
    return this;
  }

  public filterByDateTo(dateTo: string): this {
    this.params.dateTo = dateTo;
    return this;
  }

  public addCustomColumns(templates: string[]): this {
    this.params.customColumns = templates;
    return this;
  }
}
