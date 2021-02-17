# DGI Standard OAI

An implementation of OAI for DGI's standard content type.

## Requirements

This module requires the following modules/libraries:

* [Islandora](https://github.com/Islandora/islandora)
* [REST OAI-PMH](https://drupal.org/project/rest_oai_pmh)
* [Entity Reference Revisions](https://drupal.org/project/entity_reference_revisions)

## Installation

Install as usual, see
[this](https://drupal.org/documentation/install/modules-themes/modules-8) for
further information.

## Configuration

To enable the OAI implementation included here, navigate to
`admin/config/services/rest/oai-pmh`. Under 'Metadata Mappings', set the
`mdRecord` metadata type to 'DGI Standard (DPLAVA)'. This will provide metadata
at the OAI endpoint using the `mdRecord`-type `MetadataType`.

## Troubleshooting/Issues

Having problems or solved a problem? Contact
[discoverygarden](http://support.discoverygarden.ca).

## Maintainers/Sponsors

Current maintainers:

* [discoverygarden](http://www.discoverygarden.ca)

## Development

If you would like to contribute to this module create an issue, pull request
and or contact
[discoverygarden](http://support.discoverygarden.ca).

## License

[GPLv3](http://www.gnu.org/licenses/gpl-3.0.txt)
