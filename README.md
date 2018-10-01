# Cydia-Repo-API
A Cydia repo parsing API in PHP

**Live API endpoint:** https://cydia.s0n1c.org/cydia/

## Installation
- Two folders required:
  - `cache`
  - `tmp`
- Put the [index.php](index.php) in the base folder where `cache` and `tmp` are.

## Methods

`?url` Get a repo
`?q=` Search Packages by Name
`?id=` Get Package by ID 
`?pretty` Indent the JSON from Minified
`?extended` More Info (SHA256, SHA1, MD5sum, Architecture, Tag)
`?releaseOnly` Only Returns the Release Info


## Examples
`Get the Dynastic Repo` https://cydia.s0n1c.org/cydia/?id=url=https://repo.dynastic.co
`Get the Shortlook Package on Dynastic Repo` 
