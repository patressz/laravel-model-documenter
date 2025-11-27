# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## v0.3.0 - 2025-10-30

* fix: update namespace for local scope property tag in `DocBlockGenerator` (00f1e66d61bec8ebb7aab30fcf9a1a99ea9620e5)
* fix: handle model connection name when retrieving columns in `ModelDocumenter` class (54447dffbc28771ce1768c56f8eb22bdfefcfff1)
* fix: improve column retrieval by handling model connection names in `GenerateDocCommand` (552474024127b23a2bc179a715fda01d3bda7a1c)
* feat: add possibility to get model classname by argument in `GenerateDocCommand` class (0824f1524f35c7f1a2972bfab1939a0bea397c0b)
* fix: update `collection` type cast to include generics type in `ModelCastTypeResolver` (675edcf8393f1d5d1c9efd50456ba3b36ed92ba9)
* chore: remove unnecessary test (3c6f7d3b29ecf401b158d5c80f813aba842259bc)
* test: add additional test (9d326d8ef6fcf8d1ab6e742b8d7059969cb00974)

## v0.2.2 - 2025-08-07

- fixes composer packages

**Full Changelog**: https://github.com/patressz/laravel-model-documenter/compare/v0.2.1...v0.2.2

## v0.2.1 - 2025-08-07

- fixes condition if a model uses `Notifiable` trait

**Full Changelog**: https://github.com/patressz/laravel-model-documenter/compare/v0.2.0...v.0.2.1

## v0.2.0 - 2025-08-07

- Adds support for generating an property for `Notifiable` trait ($notifications)

**Full Changelog**: https://github.com/patressz/laravel-model-documenter/compare/v0.1.0...v0.2.0

## Initial release - v.0.1.0 - 2025-08-07

### Initial release

- Laravel package for automatic PHPDoc generation for Eloquent models with support for database columns, casts, relations, accessors/mutators, and scope methods

**Full Changelog**: https://github.com/patressz/laravel-model-documenter/commits/v0.1.0

## [Unreleased]

- Adds first version
