<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Find Your Representative</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>Find Your Representative</h1>
    </header>

    <div id="welcome-card" class="card">
        <p class="welcome-heading"><strong>WELCOME!</strong></p>
        <p class="welcome-text">We couldn’t find a user-friendly site for finding representatives—so we've created this modern tool to help you easily connect with your elected officials. In today's political climate, having quick access to your representatives is more important than ever.</p>
    </div>

    <div class="container card">
        <h2>Enter Your Address</h2>
        <div class="input-group">
            <input type="text" id="street" placeholder="Street Address (e.g., 123 Main St N)">
            <input type="text" id="city" placeholder="City">
            <input type="text" id="state" placeholder="State (e.g., IN)">
            <input type="text" id="zip" placeholder="ZIP Code">
        </div>
        <button onclick="findReps()">
            <i class="fas fa-search"></i> Find My Representatives
        </button>
        <div id="result"></div>
    </div>

    <footer>
        <p class="geocoding-credit">
            <i class="fas fa-info-circle"></i> Geocoding courtesy of the U.S. Census Bureau & Sponsored by Hoosier Resistance Front. Thank you!
        </p>
    </footer>

    <script>
        let csvData = null;

        function parseCSV(text) {
            const rows = [];
            let currentRow = [];
            let currentValue = "";
            let insideQuotes = false;
            
            for (let i = 0; i < text.length; i++) {
                const char = text[i];
                if (char === '"') {
                    if (insideQuotes && text[i + 1] === '"') {
                        currentValue += '"';
                        i++;
                    } else {
                        insideQuotes = !insideQuotes;
                    }
                } else if (char === "," && !insideQuotes) {
                    currentRow.push(currentValue);
                    currentValue = "";
                } else if ((char === "\n" || char === "\r") && !insideQuotes) {
                    if (currentValue || currentRow.length) {
                        currentRow.push(currentValue);
                        rows.push(currentRow);
                        currentRow = [];
                        currentValue = "";
                    }
                    if (char === "\r" && text[i+1] === "\n") i++;
                } else {
                    currentValue += char;
                }
            }
            if (currentValue || currentRow.length) {
                currentRow.push(currentValue);
                rows.push(currentRow);
            }
            return rows;
        }

        function loadCSV() {
            return fetch("Database.csv")
                .then(response => response.text())
                .then(text => { csvData = text; })
                .catch(error => console.error("Error loading CSV:", error));
        }

        async function getStateAbbreviation(fipsCode) {
            const fipsMap = {
                "01": "AL", "02": "AK", "04": "AZ", "05": "AR", "06": "CA",
                "08": "CO", "09": "CT", "10": "DE", "11": "DC", "12": "FL",
                "13": "GA", "15": "HI", "16": "ID", "17": "IL", "18": "IN",
                "19": "IA", "20": "KS", "21": "KY", "22": "LA", "23": "ME",
                "24": "MD", "25": "MA", "26": "MI", "27": "MN", "28": "MS",
                "29": "MO", "30": "MT", "31": "NE", "32": "NV", "33": "NH",
                "34": "NJ", "35": "NM", "36": "NY", "37": "NC", "38": "ND",
                "39": "OH", "40": "OK", "41": "OR", "42": "PA", "44": "RI",
                "45": "SC", "46": "SD", "47": "TN", "48": "TX", "49": "UT",
                "50": "VT", "51": "VA", "53": "WA", "54": "WV", "55": "WI",
                "56": "WY"
            };
            return fipsMap[fipsCode] || "XX";
        }

        function findLegislatorsInCSV(stateAbbrev, districtNumber) {
            if (!csvData) return { rep: null, senators: [] };
            const rows = parseCSV(csvData);
            if (rows.length < 2) return { rep: null, senators: [] };

            const headers = rows[0].map(h => h.trim());
            const dataRows = rows.slice(1).map(row => {
                const obj = {};
                headers.forEach((header, i) => {
                    obj[header] = row[i] ? row[i].trim() : "";
                });
                return obj;
            });

            const stateHouseReps = dataRows.filter(row => 
                row["Chamber"]?.toLowerCase() === "house" && 
                (row["District"] === stateAbbrev || row["District"].startsWith(`${stateAbbrev}-`))
            );

            if (stateHouseReps.length === 1) {
                return {
                    rep: stateHouseReps[0],
                    senators: dataRows.filter(row => 
                        row["Chamber"]?.toLowerCase() === "senate" && 
                        row["District"] === stateAbbrev
                    )
                };
            }

            let houseRep = null;
            const senators = [];
            const normalizedInput = `${stateAbbrev}-${districtNumber.replace(/^0+/, "")}`;

            dataRows.forEach(row => {
                const chamber = row["Chamber"]?.toLowerCase() || "";
                const csvDistrict = row["District"] || "";
                const normalizedCSV = csvDistrict.includes("-") 
                    ? `${csvDistrict.split("-")[0]}-${csvDistrict.split("-")[1].replace(/^0+/, "")}`
                    : csvDistrict;

                if (chamber === "house" && normalizedCSV === normalizedInput) {
                    houseRep = row;
                } else if (chamber === "senate" && csvDistrict === stateAbbrev) {
                    senators.push(row);
                }
            });

            return { rep: houseRep, senators };
        }

        function getPartyIcon(party) {
            if (!party) return '';
            const lowerParty = party.toLowerCase();
            if (lowerParty.includes('democrat')) return '<i class="fas fa-democrat party-icon"></i>';
            if (lowerParty.includes('republican')) return '<i class="fas fa-republican party-icon"></i>';
            return '';
        }

        function getPartyClass(party) {
            if (!party) return '';
            const lowerParty = party.toLowerCase();
            if (lowerParty.includes('democrat')) return 'democrat';
            if (lowerParty.includes('republican')) return 'republican';
            return '';
        }

        async function findReps() {
            const street = document.getElementById("street").value.trim().toUpperCase();
            const city = document.getElementById("city").value.trim().replace(/\w\S*/g, 
                txt => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
            const state = document.getElementById("state").value.trim().toUpperCase();
            const zip = document.getElementById("zip").value.trim();

            if (!street || !city || !state || !zip) {
                alert("Please fill in all fields");
                return;
            }
            if (!/^[A-Z]{2}$/.test(state)) {
                alert("Please use a valid 2-letter state abbreviation (e.g., 'CA')");
                return;
            }
            if (!/^\d{5}(-\d{4})?$/.test(zip)) {
                alert("Please enter a valid 5 or 9-digit ZIP code");
                return;
            }

            document.getElementById("welcome-card").style.display = "none";
            const resultDiv = document.getElementById("result");
            resultDiv.innerHTML = "<p>Fetching your districts...</p>";

            try {
                const proxyUrl = "https://corsproxy.io/?";
                const baseUrl = "https://geocoding.geo.census.gov/geocoder/geographies/address";
                const params = new URLSearchParams({
                    street: street,
                    city: city,
                    state: state,
                    zip: zip,
                    benchmark: "Public_AR_Current",
                    vintage: "Current_Current",
                    format: "json",
                    layers: "all"
                });

                const response = await fetch(`${proxyUrl}${baseUrl}?${params}`);
                const censusData = await response.json();
                const geographies = censusData?.result?.addressMatches?.[0]?.geographies;
                if (!geographies) {
                    resultDiv.innerHTML = "<p>Could not find geographies for your address.</p>";
                    return;
                }

                const cdInfo = geographies["119th Congressional Districts"]?.[0];
                const upperInfo = geographies["2024 State Legislative Districts - Upper"]?.[0];
                const lowerInfo = geographies["2024 State Legislative Districts - Lower"]?.[0];

                if (!cdInfo) {
                    resultDiv.innerHTML = "<p>Could not find federal district data for your address.</p>";
                    return;
                }
                if (!upperInfo || !lowerInfo) {
                    resultDiv.innerHTML = "<p>Could not find state legislative district data for your address.</p>";
                }

                const stateAbbrev = await getStateAbbreviation(cdInfo.STATE);
                const districtNumber = cdInfo.CD119;
                const { rep, senators } = findLegislatorsInCSV(stateAbbrev, districtNumber);

                let outputHtml = `
                    <div class="rep-heading-card">
                        <h3><i class="fas fa-landmark"></i> Federal Representatives</h3>
                        <p>For issues at the federal level, please contact your U.S. Representative and Senators below.</p>
                    </div>
                `;

                if (rep) {
                    const partyIcon = getPartyIcon(rep.Party);
                    const partyClass = getPartyClass(rep.Party);
                    outputHtml += `
                        <div class="official-block">
                            <h3><i class="fas fa-user-tie"></i> Your Representative</h3>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-id-badge"></i> Name:</div>
                                <div class="detail-value">${rep.Name || "N/A"}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Party:</div>
                                <div class="detail-value">
                                    <span class="${partyClass} party-display">
                                        ${partyIcon}
                                        ${rep.Party || "N/A"}
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-phone"></i> Capitol Phone:</div>
                                <div class="detail-value">${rep["Capitol Phone"] || "N/A"}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-mobile-alt"></i> District Phone:</div>
                                <div class="detail-value">${rep.district_voice || "N/A"}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-map-marker-alt"></i> Office Address:</div>
                                <div class="detail-value">${(rep["Office Address"] || "N/A").replace(/,/g, ",<br>")}</div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label"><i class="fas fa-envelope"></i> Webmail:</div>
                                <div class="detail-value">
                                    <a href="${rep["Webmail URL"] || "#"}" target="_blank">
                                        ${rep["Webmail URL"]?.split('/')[2] || "N/A"}
                                    </a>
                                </div>
                            </div>
                        </div>`;
                }

                if (senators.length > 0) {
                    outputHtml += `<h3 style="margin-top: 2rem;"><i class="fas fa-users"></i> Your Senators</h3>`;
                    senators.forEach((sen, index) => {
                        const partyIcon = getPartyIcon(sen.Party);
                        const partyClass = getPartyClass(sen.Party);
                        outputHtml += `
                            <div class="official-block">
                                <h4>Senator ${index + 1}</h4>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-id-badge"></i> Name:</div>
                                    <div class="detail-value">${sen.Name || "N/A"}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Party:</div>
                                    <div class="detail-value">
                                        <span class="${partyClass} party-display">
                                            ${partyIcon}
                                            ${sen.Party || "N/A"}
                                        </span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-phone"></i> Capitol Phone:</div>
                                    <div class="detail-value">${sen["Capitol Phone"] || "N/A"}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-mobile-alt"></i> District Phone:</div>
                                    <div class="detail-value">${sen.district_voice || "N/A"}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-map-marker-alt"></i> Office Address:</div>
                                    <div class="detail-value">${(sen["Office Address"] || "N/A").replace(/,/g, ",<br>")}</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label"><i class="fas fa-envelope"></i> Webmail:</div>
                                    <div class="detail-value">
                                        <a href="${sen["Webmail URL"] || "#"}" target="_blank">
                                            ${sen["Webmail URL"]?.split('/')[2] || "N/A"}
                                        </a>
                                    </div>
                                </div>
                            </div>`;
                    });
                }

                resultDiv.innerHTML = outputHtml;

                if (upperInfo && lowerInfo) {
                    const upperDistrict = upperInfo.BASENAME;
                    const lowerDistrict = lowerInfo.BASENAME;

                    const stateCsvUrl = stateAbbrev + ".csv";
                    fetch(stateCsvUrl)
                        .then(response => {
                            if (!response.ok) return null; 
                            return response.text();
                        })
                        .then(text => {
                            if (!text) return;

                            const stateRows = parseCSV(text);
                            if (stateRows.length < 2) return;

                            let stateOutput = `
                                <div class="rep-heading-card">
                                    <h3><i class="fas fa-landmark"></i> State Representatives</h3>
                                    <p>For issues and bills specific to your state, please contact your state legislators.</p>
                                </div>
                            `;

                            const dataRows = stateRows.slice(1); 
                            let foundAny = false;

                            dataRows.forEach(row => {
                                const name = row[0] || "N/A";
                                const party = row[1] || "N/A";
                                const dist = row[2] || "";
                                const chamber = (row[3] || "").toLowerCase();
                                const email = row[4] || "";
                                const phone = row[5] || "N/A";

                                const partyIcon = getPartyIcon(party);
                                const partyClass = getPartyClass(party);

                                if (chamber === "house" && dist === lowerDistrict) {
                                    foundAny = true;
                                    stateOutput += `
                                        <div class="official-block">
                                            <h4>State House (District ${dist})</h4>
                                            <div class="detail-item">
                                                <div class="detail-label"><i class="fas fa-id-badge"></i> Name:</div>
                                                <div class="detail-value">${name}</div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Party:</div>
                                                <div class="detail-value">
                                                    <span class="${partyClass} party-display">
                                                        ${partyIcon} ${party}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label"><i class="fas fa-envelope"></i> Email:</div>
                                                <div class="detail-value">
                                                    ${email ? `<a href="mailto:${email}">${email}</a>` : "N/A"}
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label"><i class="fas fa-phone"></i> Phone:</div>
                                                <div class="detail-value">${phone}</div>
                                            </div>
                                        </div>
                                    `;
                                } else if (chamber === "senate" && dist === upperDistrict) {
                                    foundAny = true;
                                    stateOutput += `
                                        <div class="official-block">
                                            <h4>State Senate (District ${dist})</h4>
                                            <div class="detail-item">
                                                <div class="detail-label"><i class="fas fa-id-badge"></i> Name:</div>
                                                <div class="detail-value">${name}</div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label">Party:</div>
                                                <div class="detail-value">
                                                    <span class="${partyClass} party-display">
                                                        ${partyIcon} ${party}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label"><i class="fas fa-envelope"></i> Email:</div>
                                                <div class="detail-value">
                                                    ${email ? `<a href="mailto:${email}">${email}</a>` : "N/A"}
                                                </div>
                                            </div>
                                            <div class="detail-item">
                                                <div class="detail-label"><i class="fas fa-phone"></i> Phone:</div>
                                                <div class="detail-value">${phone}</div>
                                            </div>
                                        </div>
                                    `;
                                }
                            });

                            if (!foundAny) {
                                stateOutput += `
                                    <div class="official-block">
                                        <p>No matching state-level legislators found for your address.</p>
                                    </div>
                                `;
                            }

                            resultDiv.innerHTML += stateOutput;
                        })
                        .catch(err => console.error("Error loading state CSV:", err));
                }
            } catch (error) {
                console.error("Error:", error);
                resultDiv.innerHTML = "<p>An error occurred. Please try again.</p>";
            }
        }

        window.onload = loadCSV;
    </script>
</body>
</html>