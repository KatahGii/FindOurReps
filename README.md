# Find Your Representative 🇺🇸

**A Civic Engagement Tool to Connect with Your Elected Officials**

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT) ![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple) ![CSV Data](https://img.shields.io/badge/Data-CSV-green)
## Features ✨

- 🔍 Address-Based Lookup for Federal and State Representatives
- 🗺️ Geocoding Powered by U.S. Census Bureau API
- 📊 CSV Data-Driven Representative Information
- 📈 Visit Counter with Simple PHP Tracking
- 📱 Responsive Design for All Devices
- 🛡️ CORS Proxy Implementation for API Calls
- 🏛️ Dual-Level Results (Federal & State Representatives)

## Installation 🛠️

1. **Requirements**
   - PHP 7.4+
   - Web server with write permissions

2. **Data Setup**
    
    -   Add your representative data:
        
        -   `Database.csv`  for federal officials
        -   `[STATE].csv`  files for state officials (e.g.,  `CA.csv`)
            

## Usage 🖱️

1.  Enter complete address information:
    
    -   Street Address    
    -   City
    -   State (2-letter code)
    -   ZIP Code
        
2.  View results including:
    
    -   Representative contact information
    -   Party affiliation
    -   Office addresses
    -   Direct contact links

## Data Structure 📂

Sample CSV format for representatives:
|Name    |Party     |District|Chamber|Email         |Phone       |
|--------|----------|--------|-------|--------------|------------|
|Jane Doe|Democratic|IN-5    |House  |jane@house.gov|555-123-4567|

## License 📜

MIT License - See  [LICENSE](https://license/)  file for details

## Contributing 🤝

1.  Report issues for data source changes
2.  Maintain non-partisan implementation
3.  Keep CSS/styling minimal in PRs
