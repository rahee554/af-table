# AF Table - Changelog

All notable changes to the AF Table package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.8.0] - 2025-01-15

### 🎉 Major Features Added

#### Real-Time Column Visibility System
- **Added** `wire:model.live` for instant checkbox updates without page refresh
- **Added** `updateColumnVisibility()` method for real-time state management
- **Enhanced** session-based column preference storage
- **Improved** dropdown UX - remains open while toggling columns
- **Fixed** checkbox state synchronization issues

#### Enhanced JSON Column Support
- **Added** support for multiple JSON field extractions from same column
- **Added** unique column identifiers for JSON fields (`data.name`, `data.email`)
- **Enhanced** JSON path parsing for complex nested structures
- **Improved** type safety for different JSON value types
- **Added** graceful error handling for malformed JSON

#### Smart Index Column Enhancement
- **Added** sort-aware index calculation based on current table order
- **Enhanced** pagination consistency across sorted results
- **Improved** performance with conditional index generation
- **Added** proper index numbering that reflects actual data order

#### Enhanced Delete Operations
- **Fixed** parent-child component communication for delete actions
- **Added** support for both `dispatch()` and `$parent` event patterns
- **Enhanced** error handling and user feedback for delete operations
- **Improved** automatic table refresh after successful deletions

### 🔧 Improvements

#### Performance Optimizations
- **Optimized** JSON column processing for minimal overhead
- **Improved** session data management efficiency
- **Enhanced** Livewire event handling performance
- **Reduced** unnecessary component re-renders

#### Code Quality
- **Refactored** column mapping logic for better maintainability
- **Enhanced** error handling throughout the component
- **Improved** method organization and documentation
- **Added** comprehensive inline code documentation

#### User Experience
- **Improved** visual feedback for all user interactions
- **Enhanced** responsive design for mobile devices
- **Added** better loading states and transitions
- **Improved** accessibility with ARIA labels

### 🐛 Bug Fixes
- **Fixed** JSON columns overwriting each other with duplicate keys
- **Fixed** column visibility dropdown not opening after Livewire updates
- **Fixed** delete button not working with parent component methods
- **Fixed** index column not reflecting current sort order
- **Fixed** checkbox state not updating in real-time
- **Fixed** session data not persisting across page reloads

### 📚 Documentation
- **Updated** README.md with comprehensive JSON column documentation
- **Added** real-time feature documentation and examples
- **Enhanced** TODO.md with detailed roadmap and feature tracking
- **Created** ARCHITECTURE.md with current implementation details
- **Added** CHANGELOG.md for version tracking

### ⚡ Breaking Changes
- None - All changes are backward compatible

### 🔄 Migration Guide
No migration required - all new features work seamlessly with existing implementations.

---

## [2.7.0] - 2024-12-01

### Added
- Multi-level relationship support up to 5 levels deep
- Function column improvements without mandatory key requirement
- Performance optimizations for large datasets
- Enhanced caching system for improved response times

### Fixed
- Nested relationship sorting issues
- Memory usage optimization for large exports
- Query performance improvements

### Changed
- Enhanced relationship handling logic
- Improved error messages for debugging

---

## [2.6.0] - 2024-11-01

### Added
- Basic JSON column support
- Export system improvements (Excel, CSV, PDF)
- Enhanced UI/UX with better visual feedback
- Column visibility session persistence

### Fixed
- Export performance issues
- Filter dropdown loading problems
- Search functionality edge cases

### Changed
- Modernized UI components
- Improved responsive design

---

## [2.5.0] - 2024-10-01

### Added
- Advanced filtering system with multiple filter types
- Distinct value filters with caching
- Custom query constraints support
- Function-based columns for computed values

### Fixed
- Relation column validation issues
- SQL injection prevention improvements
- Filter persistence across page loads

### Changed
- Enhanced query building logic
- Improved column detection algorithms

---

## [2.4.0] - 2024-09-01

### Added
- Comprehensive relationship support
- Raw template automatic column detection
- Enhanced export functionality
- Performance monitoring and optimization

### Fixed
- N+1 query issues with eager loading
- Memory leaks in large dataset processing
- Browser compatibility issues

### Changed
- Refactored core component architecture
- Improved code organization and maintainability

---

## [2.3.0] - 2024-08-01

### Added
- Column visibility toggle functionality
- Session-based user preferences
- Enhanced search across multiple columns
- Print-friendly table layouts

### Fixed
- Pagination issues with filtered results
- Sort direction validation problems
- Cross-browser compatibility issues

### Changed
- Updated dependencies to latest versions
- Improved error handling and user feedback

---

## [2.2.0] - 2024-07-01

### Added
- Global search functionality
- Per-column filtering capabilities
- Export options (CSV, Excel, PDF)
- Row selection with checkboxes

### Fixed
- Performance issues with large datasets
- Memory usage optimization
- Query building edge cases

### Changed
- Enhanced component architecture
- Improved documentation and examples

---

## [2.1.0] - 2024-06-01

### Added
- Dynamic column configuration
- Sorting capabilities for all column types
- Basic relationship support
- Customizable row actions

### Fixed
- Initial performance and stability issues
- Column detection and validation
- Livewire integration problems

### Changed
- Major architecture improvements
- Enhanced user interface design

---

## [2.0.0] - 2024-05-01

### Added
- Initial Laravel Livewire datatable component
- Basic column display and configuration
- Pagination support
- Simple search functionality

### Features
- Zero-configuration setup
- Eloquent model integration
- Blade template customization
- Bootstrap 5 styling

---

## Upcoming Releases

### [2.9.0] - Planned for February 2025
- Advanced filtering system overhaul
- Mobile-first responsive design
- Enhanced accessibility features
- Performance optimizations for 1000+ rows

### [3.0.0] - Planned for Q4 2025
- Complete trait-based architecture
- Plugin system for extensibility
- Advanced real-time features
- AI-powered data insights

---

## Development Guidelines

### Semantic Versioning
- **MAJOR** version for incompatible API changes
- **MINOR** version for new functionality in backward compatible manner
- **PATCH** version for backward compatible bug fixes

### Release Process
1. Feature development in feature branches
2. Comprehensive testing including performance benchmarks
3. Documentation updates
4. Code review and approval
5. Version tagging and release notes

### Contributing
- Follow PSR-12 coding standards
- Include comprehensive tests for new features
- Update documentation for all changes
- Maintain backward compatibility when possible

---

## Support & Maintenance

### Long-Term Support (LTS)
- **v2.8.x**: LTS until January 2026 (current)
- **v2.7.x**: Security fixes until July 2025
- **v2.6.x**: End of life March 2025

### Security Updates
Security vulnerabilities will be addressed in all supported versions with patch releases.

### Community Support
- GitHub Issues for bug reports and feature requests
- Documentation and examples for common use cases
- Community contributions welcome with proper testing

---

*Changelog maintained by: AF Table Development Team*  
*Last Updated: January 15, 2025*
