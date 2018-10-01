# Cydia-Repo-API
A Cydia repo parsing API in PHP

**Live API endpoint:** https://cydia.s0n1c.org/cydia/

## Methods

`?url` Get a repo

`?q=` Search Packages by Name

`?id=` Get Package by ID 

`?pretty` Indent the JSON from Minified

`?extended` More Info (SHA256, SHA1, MD5sum, Architecture, Tag)

`?releaseOnly` Only Returns the Release Info



## Examples
`Get the Dynastic Repo` https://cydia.s0n1c.org/cydia/?url=https://repo.dynastic.co

`Get the Shortlook Package on Dynastic Repo` https://cydia.s0n1c.org/cydia/?id=co.dynastic.ios.tweak.shortlook&url=https://repo.dynastic.co

`Search for the name Clean on Dynastic Repo` https://cydia.s0n1c.org/cydia/?q=Clean&url=https://repo.dynastic.co

`Prettify the results` https://cydia.s0n1c.org/cydia/?pretty&url=https://repo.dynastic.co

`Show extra information for all packages` https://cydia.s0n1c.org/cydia/?extended&url=https://repo.dynastic.co

`Return only the parsed Release File` https://cydia.s0n1c.org/cydia/?releaseOnly&url=https://repo.dynastic.co

