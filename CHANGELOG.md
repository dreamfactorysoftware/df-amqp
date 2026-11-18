# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [0.4.1] - 2026-11-18
### Changed
- A subscription keeps running when a triggered service call returns an error; the error is logged and the message acknowledged.
- Subscribe requests need an API key or a session; a request with neither returns 400.

## 0.1.0 - 2017-12-28
First official release working with the new [dreamfactory](https://github.com/dreamfactorysoftware/dreamfactory) project.
- DF-1017 Added support for AMQP (RabbitMQ)


[Unreleased]: https://github.com/dreamfactorysoftware/df-amqp/compare/0.1.0...HEAD
