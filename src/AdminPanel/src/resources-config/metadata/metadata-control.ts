export enum MetadataControl {
  TEXT = 'text',
  TEXTAREA = 'textarea',
  WYSIWYG_EDITOR = 'wysiwyg-editor',
  INTEGER = 'integer',
  DOUBLE = 'double',
  FLEXIBLE_DATE = 'flexible-date',
  DATE_RANGE = 'date-range',
  TIMESTAMP = 'timestamp',
  BOOLEAN = 'boolean',
  RELATIONSHIP = 'relationship',
  FILE = 'file',
  DIRECTORY = 'directory',
  DISPLAY_STRATEGY = 'display-strategy',
  SYSTEM_LANGUAGE = 'system-language'
}

export const filterableControls = [
  MetadataControl.TEXT,
  MetadataControl.TEXTAREA,
  MetadataControl.INTEGER,
  MetadataControl.RELATIONSHIP,
  MetadataControl.DISPLAY_STRATEGY,
  MetadataControl.SYSTEM_LANGUAGE
];
