document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    let storedTheme = null;
    try {
        storedTheme = window.localStorage.getItem('digihome-theme');
    } catch (error) {
        storedTheme = null;
    }
    if (storedTheme === 'light' || storedTheme === 'dark') {
        body.setAttribute('data-theme', storedTheme);
    }

    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
        const syncThemeIcon = () => {
            const mode = body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            if (mode === 'dark') {
                toggle.classList.add('is-dark');
                toggle.setAttribute('aria-label', 'Switch to light theme');
                toggle.setAttribute('title', 'Switch to light theme');
            } else {
                toggle.classList.remove('is-dark');
                toggle.setAttribute('aria-label', 'Switch to dark theme');
                toggle.setAttribute('title', 'Switch to dark theme');
            }
        };
        syncThemeIcon();
        toggle.addEventListener('click', () => {
            const current = body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            body.setAttribute('data-theme', next);
            try {
                window.localStorage.setItem('digihome-theme', next);
            } catch (error) {
                // Ignore storage issues (private mode/restricted storage) and keep in-memory theme.
            }
            syncThemeIcon();
        });
    });

    const loader = document.querySelector('[data-page-loader]');
    window.requestAnimationFrame(() => {
        body.classList.add('is-ready');
        loader?.classList.add('is-hidden');
    });

    const navToggle = document.querySelector('[data-nav-toggle]');
    const navShell = document.querySelector('[data-nav-shell]');
    const navClose = document.querySelector('[data-nav-close]');
    const setNavState = (isOpen) => {
        if (!navToggle || !navShell) {
            return;
        }
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        navShell.classList.toggle('is-open', isOpen);
        body.classList.toggle('nav-open', isOpen);
    };

    navToggle?.addEventListener('click', () => {
        const expanded = navToggle.getAttribute('aria-expanded') === 'true';
        setNavState(!expanded);
    });

    navClose?.addEventListener('click', () => setNavState(false));

    document.addEventListener('click', (event) => {
        if (!navToggle || !navShell) {
            return;
        }
        const expanded = navToggle.getAttribute('aria-expanded') === 'true';
        if (!expanded) {
            return;
        }
        const target = event.target;
        if (!(target instanceof Node)) {
            return;
        }
        const clickedInsideNav = navShell.contains(target);
        const clickedToggle = navToggle.contains(target);
        if (!clickedInsideNav && !clickedToggle) {
            setNavState(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setNavState(false);
        }
    });

    navShell?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setNavState(false));
    });

    document.querySelectorAll('[data-alert]').forEach((alertBox) => {
        window.setTimeout(() => {
            alertBox.classList.add('is-dismissed');
        }, 4500);
    });

    document.querySelectorAll('[data-confirm-logout="true"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const ok = window.confirm('Are you sure you want to logout from this role?');
            if (!ok) {
                event.preventDefault();
            }
        });
    });

    const payButton = document.querySelector('[data-pay-button]');
    payButton?.addEventListener('click', () => {
        payButton.textContent = 'Processing unlock request...';
    });

    const revealItems = document.querySelectorAll('[data-reveal]');
    if (revealItems.length > 0) {
        // Fail-safe: keep content visible even if observer or later JS fails.
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }

    document.querySelectorAll('.field-card').forEach((fieldCard) => {
        const requiredControl = fieldCard.querySelector('input[required], select[required], textarea[required]');
        if (requiredControl) {
            fieldCard.classList.add('is-required');
        }
    });

    if ('IntersectionObserver' in window && revealItems.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        revealItems.forEach((item) => observer.observe(item));
    }

    const categorySelector = document.getElementById('listing-category');
    const bedroomsInput = document.getElementById('bedrooms');
    const bathroomsInput = document.getElementById('bathrooms');
    const commercialTargets = document.querySelectorAll('[data-residential-field]');

    const toggleResidentialFields = () => {
        const category = categorySelector?.value || 'residential';
        const isCommercial = category === 'office' || category === 'business';
        commercialTargets.forEach((field) => field.classList.toggle('is-disabled', isCommercial));
        if (bedroomsInput) {
            bedroomsInput.disabled = isCommercial;
            if (isCommercial && bedroomsInput.value === '') {
                bedroomsInput.value = '0';
            }
        }
        if (bathroomsInput) {
            bathroomsInput.disabled = isCommercial;
            if (isCommercial && bathroomsInput.value === '') {
                bathroomsInput.value = '0';
            }
        }
    };

    categorySelector?.addEventListener('change', toggleResidentialFields);
    toggleResidentialFields();

    const locationInputs = {
        country: document.querySelector('input[name="country"][data-location-select="country"]'),
        county: document.querySelector('input[name="county"][data-location-select="county"]'),
        subCounty: document.querySelector('input[name="sub_county"][data-location-select="sub_county"]'),
        ward: document.querySelector('input[name="ward"][data-location-select="ward"]'),
        town: document.querySelector('input[name="town"]'),
        estate: document.querySelector('input[name="estate"]'),
        location: document.querySelector('input[name="location"]'),
        cityAlias: document.querySelector('input[name="city"][type="hidden"]'),
        postalAddress: document.querySelector('input[name="postal_address"]'),
        postalCodeAlias: document.querySelector('input[name="postal_code"][type="hidden"]'),
        unitNumber: document.querySelector('input[name="unit_number"]'),
        roomAlias: document.querySelector('input[name="room_number"][type="hidden"]'),
    };

    if (locationInputs.county && locationInputs.subCounty && locationInputs.ward) {
        const fallbackLocationData = {
            Kenya: {
                Nairobi: {
                    Westlands: ['Parklands', 'Kitisuru', 'Karura'],
                    'Kasarani': ['Mwiki', 'Clay City', 'Ruai'],
                    Embakasi: ['Upper Savannah', 'Kwa Njenga', 'Utawala'],
                    Dagoretti: ['Kawangware', 'Mutu-ini', 'Ngando'],
                },
                Kiambu: {
                    Ruiru: ['Gitothua', 'Kahawa Sukari', 'Mwiki'],
                    Thika: ['Township', 'Hospital', 'Kamenu'],
                    Juja: ['Kalimoni', 'Witeithie', 'Murera'],
                },
                Mombasa: {
                    Nyali: ['Frere Town', 'Kongowea', 'Kadzandani'],
                    Kisauni: ['Mjambere', 'Junda', 'Bamburi'],
                },
                Machakos: {
                    'Athi River': ['Kinanie', 'Mavoko', 'Syokimau'],
                },
            },
        };
        const runtimeLocationData = (typeof window !== 'undefined' && window.DIGIHOME_LOCATION_DATA && typeof window.DIGIHOME_LOCATION_DATA === 'object')
            ? window.DIGIHOME_LOCATION_DATA
            : {};
        const locationData = Object.keys(runtimeLocationData).length > 0 ? runtimeLocationData : fallbackLocationData;

        const normalize = (value) => String(value || '').trim().toLowerCase();
        const getActiveCountry = () => {
            if (!locationInputs.country) {
                return 'Kenya';
            }

            const typed = locationInputs.country.value.trim();
            return typed === '' ? 'Kenya' : typed;
        };

        const locationFields = [];
        if (locationInputs.country) {
            locationFields.push({
                key: 'country',
                input: locationInputs.country,
                options: () => Object.keys(locationData),
                clearChildren: () => {},
            });
        }
        locationFields.push(
            {
                key: 'county',
                input: locationInputs.county,
                options: () => getCountyValues(),
                clearChildren: () => {
                    locationInputs.subCounty.value = '';
                    locationInputs.ward.value = '';
                },
            },
            {
                key: 'subCounty',
                input: locationInputs.subCounty,
                options: () => getSubCountyValues(),
                clearChildren: () => {
                    locationInputs.ward.value = '';
                },
            },
            {
                key: 'ward',
                input: locationInputs.ward,
                options: () => getWardValues(),
                clearChildren: () => {},
            },
        );

        const getCanonicalCountry = () => {
            const typed = getActiveCountry();
            const countries = Object.keys(locationData);
            const exact = countries.find((country) => normalize(country) === normalize(typed));
            return exact || typed || 'Kenya';
        };

        const getCountyValues = () => {
            const country = getCanonicalCountry();
            return Object.keys(locationData[country] || {});
        };

        const getCanonicalCounty = () => {
            const typed = locationInputs.county.value.trim();
            const counties = getCountyValues();
            const exact = counties.find((county) => normalize(county) === normalize(typed));
            return exact || typed;
        };

        const getSubCountyValues = () => {
            const country = getCanonicalCountry();
            const county = getCanonicalCounty();
            return Object.keys((locationData[country] || {})[county] || {});
        };

        const getCanonicalSubCounty = () => {
            const typed = locationInputs.subCounty.value.trim();
            const subCounties = getSubCountyValues();
            const exact = subCounties.find((subCounty) => normalize(subCounty) === normalize(typed));
            return exact || typed;
        };

        const getWardValues = () => {
            const country = getCanonicalCountry();
            const county = getCanonicalCounty();
            const subCounty = getCanonicalSubCounty();
            return ((locationData[country] || {})[county] || {})[subCounty] || [];
        };

        const createdCombos = new WeakSet();
        const closeAllLocationMenus = () => {
            document.querySelectorAll('.location-combo.is-open').forEach((combo) => {
                combo.classList.remove('is-open');
            });
        };

        const renderLocationMenu = (field, showAll = false) => {
            const combo = field.input.closest('.location-combo');
            const menu = combo?.querySelector('.location-combo-menu');
            if (!menu) {
                return;
            }

            const values = field.options();
            const typed = field.input.value.trim();
            const needle = normalize(typed);
            const filtered = values.filter((value) => {
                const candidate = String(value || '').trim();
                if (!candidate) {
                    return false;
                }
                return showAll || needle === '' || normalize(candidate).includes(needle);
            });

            menu.innerHTML = filtered.length > 0
                ? filtered.map((value) => `<button type="button" class="location-combo-option" data-value="${value.replace(/"/g, '&quot;')}">${value}</button>`).join('')
                : '<div class="location-combo-empty">No matching options</div>';
        };

        const openLocationMenu = (field, showAll = false) => {
            const combo = field.input.closest('.location-combo');
            if (!combo) {
                return;
            }

            closeAllLocationMenus();
            renderLocationMenu(field, showAll);
            combo.classList.add('is-open');
        };

        const clearChildSelections = (fieldKey) => {
            if (fieldKey === 'country') {
                locationInputs.county.value = '';
                locationInputs.subCounty.value = '';
                locationInputs.ward.value = '';
                return;
            }

            if (fieldKey === 'county') {
                locationInputs.subCounty.value = '';
                locationInputs.ward.value = '';
                return;
            }

            if (fieldKey === 'subCounty') {
                locationInputs.ward.value = '';
            }
        };

        const isValueInOptions = (fieldKey, value) => {
            const current = String(value || '').trim();
            if (current === '') {
                return true;
            }

            if (fieldKey === 'country') {
                return Object.keys(locationData).some((country) => normalize(country) === normalize(current));
            }

            if (fieldKey === 'county') {
                return getCountyValues().some((county) => normalize(county) === normalize(current));
            }

            if (fieldKey === 'subCounty') {
                return getSubCountyValues().some((subCounty) => normalize(subCounty) === normalize(current));
            }

            if (fieldKey === 'ward') {
                return getWardValues().some((ward) => normalize(ward) === normalize(current));
            }

            return false;
        };

        const syncFieldChange = (field, previousValue = '') => {
            const currentValue = String(field.input.value || '').trim();
            const valueChanged = normalize(previousValue) !== normalize(currentValue);
            const invalidValue = currentValue !== '' && !isValueInOptions(field.key, currentValue);

            if (valueChanged || invalidValue) {
                clearChildSelections(field.key);
            }

            field.input.dataset.lastCommittedValue = currentValue;
        };

        const ensureComboShell = (field) => {
            if (createdCombos.has(field.input)) {
                return;
            }

            const input = field.input;
            const fieldCard = input.closest('.field-card');
            if (!fieldCard) {
                return;
            }

            input.removeAttribute('list');
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('aria-autocomplete', 'list');

            const existingShell = fieldCard.querySelector('.location-combo');
            if (existingShell) {
                createdCombos.add(input);
                return;
            }

            const shell = document.createElement('div');
            shell.className = 'location-combo';

            input.parentNode.insertBefore(shell, input);
            shell.appendChild(input);

            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'location-combo-toggle';
            toggle.setAttribute('aria-label', `Show ${field.key} options`);
            toggle.innerHTML = '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i>';
            shell.appendChild(toggle);

            const menu = document.createElement('div');
            menu.className = 'location-combo-menu';
            menu.setAttribute('role', 'listbox');
            shell.appendChild(menu);

            input.addEventListener('focus', () => openLocationMenu(field));
            input.addEventListener('click', () => openLocationMenu(field));
            input.addEventListener('focus', () => {
                input.dataset.lastCommittedValue = String(input.value || '').trim();
            });
            input.addEventListener('input', () => {
                renderLocationMenu(field, false);
                syncFieldChange(field, input.dataset.lastCommittedValue || '');
            });
            input.addEventListener('change', () => {
                syncFieldChange(field, input.dataset.lastCommittedValue || '');
                renderLocationMenu(field, false);
            });
            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const isOpen = shell.classList.contains('is-open');
                if (isOpen) {
                    shell.classList.remove('is-open');
                    return;
                }
                openLocationMenu(field, true);
            });
            menu.addEventListener('click', (event) => {
                const option = event.target.closest('.location-combo-option');
                if (!option) {
                    return;
                }
                input.value = option.getAttribute('data-value') || '';
                shell.classList.remove('is-open');
                field.clearChildren();
                input.dataset.lastCommittedValue = String(input.value || '').trim();
                syncDerivedLocation();
                locationFields.forEach((nextField) => renderLocationMenu(nextField, false));
            });

            createdCombos.add(input);
        };

        locationFields.forEach((field) => ensureComboShell(field));

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.location-combo')) {
                closeAllLocationMenus();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllLocationMenus();
            }
        });

        const syncDerivedLocation = () => {
            const parts = [
                locationInputs.estate?.value,
                locationInputs.ward?.value,
                locationInputs.subCounty?.value,
                locationInputs.county?.value,
                locationInputs.country?.value || 'Kenya',
            ]
                .map((value) => String(value || '').trim())
                .filter(Boolean);
            if (locationInputs.location) {
                locationInputs.location.value = parts.join(', ');
            }
            if (locationInputs.town) {
                const townParts = [locationInputs.subCounty?.value, locationInputs.ward?.value]
                    .map((value) => String(value || '').trim())
                    .filter(Boolean);
                locationInputs.town.value = townParts.join(', ');
            }
            if (locationInputs.cityAlias) {
                locationInputs.cityAlias.value = String(locationInputs.subCounty?.value || '').trim();
            }
            if (locationInputs.postalAddress && locationInputs.postalCodeAlias) {
                locationInputs.postalCodeAlias.value = String(locationInputs.postalAddress.value || '').trim();
            }
            if (locationInputs.unitNumber && locationInputs.roomAlias) {
                locationInputs.roomAlias.value = String(locationInputs.unitNumber.value || '').trim();
            }
        };

        if (locationInputs.country && locationInputs.country.value.trim() === '') {
            locationInputs.country.value = 'Kenya';
        }

        locationFields.forEach((field) => renderLocationMenu(field, true));
        syncDerivedLocation();

        locationInputs.country?.addEventListener('input', () => {
            renderLocationMenu(locationFields[0], false);
            renderLocationMenu(locationFields[1], false);
            renderLocationMenu(locationFields[2], false);
            renderLocationMenu(locationFields[3], false);
            syncDerivedLocation();
        });
        locationInputs.county.addEventListener('input', () => {
            renderLocationMenu(locationFields[locationInputs.country ? 1 : 0], false);
            renderLocationMenu(locationFields[locationInputs.country ? 2 : 1], false);
            renderLocationMenu(locationFields[locationInputs.country ? 3 : 2], false);
            syncDerivedLocation();
        });
        locationInputs.subCounty.addEventListener('input', () => {
            renderLocationMenu(locationFields[locationInputs.country ? 2 : 1], false);
            renderLocationMenu(locationFields[locationInputs.country ? 3 : 2], false);
            syncDerivedLocation();
        });
        locationInputs.ward.addEventListener('input', () => {
            renderLocationMenu(locationFields[locationFields.length - 1], false);
            syncDerivedLocation();
        });

        ['change', 'blur'].forEach((eventName) => {
            locationInputs.country?.addEventListener(eventName, syncDerivedLocation);
            locationInputs.county.addEventListener(eventName, syncDerivedLocation);
            locationInputs.subCounty.addEventListener(eventName, syncDerivedLocation);
            locationInputs.ward.addEventListener(eventName, syncDerivedLocation);
            locationInputs.estate?.addEventListener(eventName, syncDerivedLocation);
            locationInputs.postalAddress?.addEventListener(eventName, syncDerivedLocation);
            locationInputs.unitNumber?.addEventListener(eventName, syncDerivedLocation);
        });

        locationFields.forEach((field) => {
            field.input.addEventListener('focus', () => {
                renderLocationMenu(field, true);
            });
        });
    }

    const tabTriggers = document.querySelectorAll('[data-tab-trigger]');
    const tabPanes = document.querySelectorAll('[data-tab-pane]');
    const activateTab = (tabName) => {
        if (!tabName) {
            return;
        }
        tabTriggers.forEach((trigger) => {
            const isActive = trigger.getAttribute('data-tab-trigger') === tabName;
            trigger.classList.toggle('is-active', isActive);
            trigger.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
        tabPanes.forEach((pane) => {
            pane.classList.toggle('is-active', pane.getAttribute('data-tab-pane') === tabName);
        });
    };

    tabTriggers.forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const tabName = trigger.getAttribute('data-tab-trigger');
            activateTab(tabName);
        });
    });

    const ownerWizardForm = document.querySelector('form.wizard-form');
    const ownerWizardSteps = Array.from(document.querySelectorAll('.wizard-progress .step'));
    if (ownerWizardForm && ownerWizardSteps.length > 0) {
        ownerWizardSteps.forEach((stepLink) => {
            stepLink.addEventListener('click', (event) => {
                event.preventDefault();

                let targetStep = '';
                try {
                    const url = new URL(stepLink.getAttribute('href') || '', window.location.origin);
                    targetStep = url.searchParams.get('step') || '';
                } catch (error) {
                    targetStep = '';
                }

                const targetInput = ownerWizardForm.querySelector('input[name="target_tab"]');
                if (targetInput instanceof HTMLInputElement && targetStep !== '') {
                    targetInput.value = targetStep;
                }

                let actionInput = ownerWizardForm.querySelector('input[name="action"][data-auto-draft]');
                if (!(actionInput instanceof HTMLInputElement)) {
                    actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.dataset.autoDraft = '1';
                    ownerWizardForm.appendChild(actionInput);
                }
                actionInput.value = 'navigate_tab';

                ownerWizardForm.submit();
            });
        });
    }

    const searchToggle = document.querySelector('[data-search-toggle]');
    const searchPanel = document.querySelector('[data-search-panel]');
    searchToggle?.addEventListener('click', () => {
        if (!searchPanel) {
            return;
        }
        const collapsed = searchPanel.classList.contains('is-collapsed');
        searchPanel.classList.toggle('is-collapsed', !collapsed);
        searchToggle.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
        searchToggle.textContent = collapsed ? 'Hide search filters' : 'Show search filters';
    });

    document.querySelectorAll('.remembered-carousel').forEach((carousel) => {
        const card = carousel.closest('.account-card');
        if (!card) {
            return;
        }
        const slides = Array.from(carousel.querySelectorAll('[data-account-slide]'));
        const prev = card.querySelector('[data-account-prev]');
        const next = card.querySelector('[data-account-next]');
        const counters = Array.from(card.querySelectorAll('[data-account-counter]'));
        if (slides.length <= 1) {
            if (prev) prev.disabled = true;
            if (next) next.disabled = true;
            return;
        }

        let index = 0;
        const render = () => {
            slides.forEach((slide, i) => {
                slide.classList.toggle('is-active', i === index);
            });
            counters.forEach((counter) => {
                counter.textContent = `${index + 1}/${slides.length}`;
            });
        };

        prev?.addEventListener('click', () => {
            index = (index - 1 + slides.length) % slides.length;
            render();
        });
        next?.addEventListener('click', () => {
            index = (index + 1) % slides.length;
            render();
        });
        render();
    });

    document.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
        const ensureIcon = () => {
            let icon = toggleButton.querySelector('i');
            if (!icon) {
                toggleButton.textContent = '';
                icon = document.createElement('i');
                icon.className = 'fa-solid fa-eye';
                icon.setAttribute('aria-hidden', 'true');
                toggleButton.appendChild(icon);
            }
            return icon;
        };

        const icon = ensureIcon();
        toggleButton.addEventListener('click', () => {
            const target = toggleButton.getAttribute('data-password-toggle');
            const input = target ? document.getElementById(target) : null;
            if (!input) {
                return;
            }
            const nextType = input.type === 'password' ? 'text' : 'password';
            input.type = nextType;
            icon.classList.toggle('fa-eye', nextType === 'password');
            icon.classList.toggle('fa-eye-slash', nextType !== 'password');
            toggleButton.setAttribute('aria-label', nextType === 'password' ? 'Show password' : 'Hide password');
            toggleButton.setAttribute('title', nextType === 'password' ? 'Show password' : 'Hide password');
        });
    });

    document.querySelectorAll('[data-copy-link]').forEach((copyLink) => {
        copyLink.addEventListener('click', async (event) => {
            event.preventDefault();

            const sourceValue = copyLink.getAttribute('data-copy-link') || copyLink.getAttribute('href') || '';
            const text = String(sourceValue || '').trim();
            if (!text) {
                return;
            }

            const fallbackCopy = () => {
                const helper = document.createElement('textarea');
                helper.value = text;
                helper.setAttribute('readonly', 'readonly');
                helper.style.position = 'fixed';
                helper.style.left = '-9999px';
                document.body.appendChild(helper);
                helper.select();
                document.execCommand('copy');
                helper.remove();
            };

            try {
                if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                    await navigator.clipboard.writeText(text);
                } else {
                    fallbackCopy();
                }
            } catch (error) {
                fallbackCopy();
            }

            copyLink.setAttribute('title', 'Copied link');
            window.setTimeout(() => {
                copyLink.setAttribute('title', 'Click to copy link');
            }, 1500);
        });
    });

    document.querySelectorAll('[data-scroll-target]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const selector = trigger.getAttribute('data-scroll-target');
            if (!selector) {
                return;
            }

            const target = document.querySelector(selector);
            if (!(target instanceof HTMLElement)) {
                return;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof target.focus === 'function') {
                target.focus({ preventScroll: true });
            }
        });
    });

    const profileInput = document.querySelector('[data-profile-input]');
    const profilePreview = document.querySelector('[data-profile-preview]');
    profileInput?.addEventListener('change', () => {
        const file = profileInput.files && profileInput.files[0] ? profileInput.files[0] : null;
        if (!file || !profilePreview) {
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                profilePreview.setAttribute('src', reader.result);
            }
        };
        reader.readAsDataURL(file);
    });

    const companyLogoInput = document.querySelector('[data-company-logo-input]');
    const companyLogoPreview = document.querySelector('[data-company-logo-preview]');
    companyLogoInput?.addEventListener('change', () => {
        const file = companyLogoInput.files && companyLogoInput.files[0] ? companyLogoInput.files[0] : null;
        if (!file || !companyLogoPreview) {
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                companyLogoPreview.setAttribute('src', reader.result);
            }
        };
        reader.readAsDataURL(file);
    });

    const enforceCoverHiddenRule = (root = document, preferredCoverValue = null) => {
        const coverInputs = Array.from(root.querySelectorAll('input[name="cover_image_index"]'));
        const hiddenChecks = Array.from(root.querySelectorAll('input[name="hidden_image_flags[]"]'));
        if (coverInputs.length === 0 && hiddenChecks.length === 0) {
            return;
        }

        let selectedCover = null;
        if (preferredCoverValue !== null) {
            const preferredInput = coverInputs.find((input) => input.value === String(preferredCoverValue) && input.checked && !input.disabled);
            if (preferredInput) {
                selectedCover = preferredInput.value;
            }
        }
        coverInputs.forEach((input) => {
            if (!input.checked) {
                return;
            }
            if (selectedCover === null) {
                selectedCover = input.value;
                return;
            }
            // Keep exactly one checked cover input (first checked wins).
            input.checked = false;
        });
        hiddenChecks.forEach((checkbox) => {
            const isCover = selectedCover !== null && checkbox.value === selectedCover;
            checkbox.disabled = isCover;
            if (isCover) {
                checkbox.checked = false;
            }
        });

        const hiddenValues = new Set(hiddenChecks.filter((check) => check.checked).map((check) => check.value));
        coverInputs.forEach((input) => {
            const isHidden = hiddenValues.has(input.value);
            if (isHidden && input.checked) {
                input.checked = false;
            }
            input.disabled = isHidden;
        });
    };

    document.querySelectorAll('[data-image-builder]').forEach((builder) => {
        const offset = Number.parseInt(builder.getAttribute('data-index-offset') || '0', 10) || 0;
        const list = document.createElement('div');
        list.className = 'property-image-builder-list';
        builder.appendChild(list);

        let counter = 0;
        const makeCard = (index) => {
            const card = document.createElement('article');
            card.className = 'property-image-builder-item';
            card.dataset.builderIndex = String(index);

            const fileLabel = document.createElement('label');
            fileLabel.textContent = 'Select image';
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = 'property_images[]';
            fileInput.accept = 'image/*';
            fileInput.required = false;
            fileLabel.appendChild(fileInput);
            card.appendChild(fileLabel);

            const preview = document.createElement('img');
            preview.alt = `Property image ${index + 1}`;
            preview.hidden = true;
            card.appendChild(preview);

            const descriptionLabel = document.createElement('label');
            descriptionLabel.textContent = 'Image description';
            const descriptionInput = document.createElement('input');
            descriptionInput.type = 'text';
            descriptionInput.name = 'new_image_descriptions[]';
            descriptionInput.placeholder = 'Describe this image';
            descriptionInput.disabled = true;
            descriptionLabel.appendChild(descriptionInput);
            card.appendChild(descriptionLabel);

            const controls = document.createElement('div');
            controls.className = 'property-image-builder-controls';

            const coverLabel = document.createElement('label');
            const coverInput = document.createElement('input');
            coverInput.type = 'checkbox';
            coverInput.name = 'cover_image_index';
            coverInput.value = String(offset + index);
            coverInput.disabled = true;
            coverLabel.appendChild(coverInput);
            coverLabel.append(' Cover image');
            controls.appendChild(coverLabel);

            const hiddenLabel = document.createElement('label');
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'checkbox';
            hiddenInput.name = 'hidden_image_flags[]';
            hiddenInput.value = String(offset + index);
            hiddenInput.disabled = true;
            hiddenLabel.appendChild(hiddenInput);
            hiddenLabel.append(' Hidden image');
            controls.appendChild(hiddenLabel);
            card.appendChild(controls);

            fileInput.addEventListener('change', () => {
                const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                if (!file) {
                    preview.hidden = true;
                    preview.removeAttribute('src');
                    descriptionInput.disabled = true;
                    descriptionInput.value = '';
                    coverInput.disabled = true;
                    coverInput.checked = false;
                    hiddenInput.disabled = true;
                    hiddenInput.checked = false;
                    enforceCoverHiddenRule(builder.closest('form') || document);
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    fileInput.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = () => {
                    if (typeof reader.result === 'string') {
                        preview.src = reader.result;
                        preview.hidden = false;
                    }
                };
                reader.readAsDataURL(file);

                descriptionInput.disabled = false;
                coverInput.disabled = false;
                hiddenInput.disabled = false;

                const hasSelectedCover = Array.from(document.querySelectorAll('input[name="cover_image_index"]')).some((input) => input.checked);
                if (!hasSelectedCover) {
                    coverInput.checked = true;
                }

                const currentCards = Array.from(list.querySelectorAll('.property-image-builder-item'));
                const isLastCard = currentCards[currentCards.length - 1] === card;
                if (isLastCard) {
                    counter += 1;
                    list.appendChild(makeCard(counter));
                }
                enforceCoverHiddenRule(builder.closest('form') || document);
            });

            coverInput.addEventListener('change', () => {
                enforceCoverHiddenRule(builder.closest('form') || document, coverInput.checked ? coverInput.value : null);
            });
            hiddenInput.addEventListener('change', () => {
                enforceCoverHiddenRule(builder.closest('form') || document);
            });

            return card;
        };

        list.appendChild(makeCard(counter));
    });

    document.querySelectorAll('form').forEach((formNode) => {
        formNode.addEventListener('change', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }
            if (target.name === 'cover_image_index' || target.name === 'hidden_image_flags[]') {
                enforceCoverHiddenRule(
                    formNode,
                    target.name === 'cover_image_index' && target.checked ? target.value : null
                );
            }
        });
        enforceCoverHiddenRule(formNode);
    });

    document.querySelectorAll('.nav-menu a').forEach((link) => {
        if (link.getAttribute('title')) {
            return;
        }
        const label = (link.querySelector('.nav-label')?.textContent || link.textContent || '').trim();
        if (label !== '') {
            link.setAttribute('title', label);
            if (!link.getAttribute('aria-label')) {
                link.setAttribute('aria-label', label);
            }
        }
    });

    const galleryItems = Array.from(document.querySelectorAll('[data-gallery-item]'));
    const lightbox = document.querySelector('[data-gallery-lightbox]');
    const lightboxImage = document.querySelector('[data-gallery-lightbox-image]');
    const lightboxCaption = document.querySelector('[data-gallery-lightbox-caption]');
    const galleryStrip = document.querySelector('[data-gallery-strip]');
    let activeGalleryIndex = 0;

    if (galleryItems.length > 0 && lightbox && lightboxImage && lightboxCaption && galleryStrip) {
        let touchStartX = 0;
        let touchEndX = 0;
        const images = galleryItems.map((item) => {
            const image = item.querySelector('[data-gallery-image]');
            const caption = item.querySelector('figcaption');
            return {
                src: image?.getAttribute('src') || '',
                alt: image?.getAttribute('alt') || 'Property image',
                caption: caption?.textContent || '',
            };
        });

        const renderLightbox = () => {
            const current = images[activeGalleryIndex];
            lightboxImage.setAttribute('src', current.src);
            lightboxImage.setAttribute('alt', current.alt);
            lightboxCaption.textContent = current.caption;
            galleryStrip.innerHTML = '';
            images.forEach((image, index) => {
                const thumb = document.createElement('img');
                thumb.src = image.src;
                thumb.alt = image.alt;
                thumb.className = index === activeGalleryIndex ? 'is-active' : '';
                thumb.addEventListener('click', () => {
                    activeGalleryIndex = index;
                    renderLightbox();
                });
                galleryStrip.appendChild(thumb);
            });
        };

        const goPrevImage = () => {
            activeGalleryIndex = (activeGalleryIndex - 1 + images.length) % images.length;
            renderLightbox();
        };

        const goNextImage = () => {
            activeGalleryIndex = (activeGalleryIndex + 1) % images.length;
            renderLightbox();
        };

        galleryItems.forEach((item, index) => {
            item.addEventListener('click', () => {
                activeGalleryIndex = index;
                lightbox.hidden = false;
                renderLightbox();
            });
        });

        document.querySelector('[data-gallery-close]')?.addEventListener('click', () => {
            lightbox.hidden = true;
        });
        lightbox.addEventListener('click', (event) => {
            if (event.target === lightbox) {
                lightbox.hidden = true;
            }
        });
        document.querySelector('[data-gallery-prev]')?.addEventListener('click', () => {
            goPrevImage();
        });
        document.querySelector('[data-gallery-next]')?.addEventListener('click', () => {
            goNextImage();
        });

        document.addEventListener('keydown', (event) => {
            if (lightbox.hidden) {
                return;
            }
            if (event.key === 'ArrowLeft') {
                goPrevImage();
            } else if (event.key === 'ArrowRight') {
                goNextImage();
            } else if (event.key === 'Escape') {
                lightbox.hidden = true;
            }
        });

        lightbox.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0]?.clientX || 0;
        }, { passive: true });

        lightbox.addEventListener('touchend', (event) => {
            touchEndX = event.changedTouches[0]?.clientX || 0;
            const delta = touchEndX - touchStartX;
            if (Math.abs(delta) < 36) {
                return;
            }
            if (delta > 0) {
                goPrevImage();
            } else {
                goNextImage();
            }
        }, { passive: true });
    }

    const chatApp = document.querySelector('[data-chat-app]');
    const chatFloatShell = document.querySelector('[data-chat-float]');
    const navChatLinks = Array.from(document.querySelectorAll('.nav-menu a[href*="/chat.php"]'));
    if (chatApp || chatFloatShell || navChatLinks.length > 0) {
        const chatStateUrl = chatApp?.getAttribute('data-chat-state-url') || '/DigiHome/includes/chat-api.php';
        const chatUserId = Number.parseInt(chatApp?.getAttribute('data-chat-user-id') || '0', 10) || 0;
        const chatRole = (chatApp?.getAttribute('data-chat-role') || chatFloatShell?.getAttribute('data-chat-role') || '').toLowerCase();
        const chatAuthToken = chatApp?.getAttribute('data-chat-auth-token') || chatFloatShell?.getAttribute('data-chat-auth-token') || '';
        const chatThread = chatApp?.querySelector('[data-chat-thread]') || null;
        const chatTyping = chatApp?.querySelector('[data-chat-typing]') || null;
        const chatCompose = chatApp?.querySelector('[data-chat-compose]') || null;
        const chatMessageInput = chatApp?.querySelector('[data-chat-message-input]') || null;
        const chatConversationInput = chatApp?.querySelector('[data-chat-conversation-id]') || null;
        const chatList = chatApp?.querySelector('[data-chat-list]') || null;
        const chatPageStatus = document.querySelector('.chat-page-status');
        const chatClosePanelButtons = chatApp
            ? Array.from(chatApp.querySelectorAll('[data-chat-close-panel]'))
            : [];
        const chatScopeLabel = chatApp?.querySelector('[data-chat-scope-label]') || null;
        const chatRecipientPresence = chatApp?.querySelector('[data-chat-recipient-presence]') || null;
        const chatPeerAvatar = chatApp?.querySelector('[data-chat-peer-avatar]') || null;
        const chatPeerName = chatApp?.querySelector('[data-chat-peer-name]') || null;
        const chatPeerStatus = chatApp?.querySelector('[data-chat-peer-status]') || null;
        const chatPeerTyping = chatApp?.querySelector('[data-chat-peer-typing]') || null;
        const nonAdminPeerBanner = chatApp?.querySelector('.chat-peer-banner') || null;
        const nonAdminPeerBannerName = nonAdminPeerBanner?.querySelector('.chat-peer-name') || null;
        const nonAdminPeerBannerStatus = nonAdminPeerBanner?.querySelector('.chat-peer-status') || null;
        const chatConversationMeta = chatApp?.querySelector('.chat-conversation-meta') || null;
        const chatFloatBadge = document.querySelector('[data-chat-float-badge]');
        const chatFloatStatus = document.querySelector('[data-chat-float-status]');
        const chatStartForm = document.querySelector('[data-chat-start-form]');
        const chatVisibilitySelect = document.querySelector('[data-chat-visibility-select]');
        const chatRecipientSelect = document.querySelector('[data-chat-recipient-select]');
        const chatRoleFilter = document.querySelector('[data-chat-role-filter]');
        const chatRecipientMeta = document.querySelector('[data-chat-recipient-meta]');
        const chatRecipientRoleInput = document.querySelector('[data-chat-recipient-role]');
        const chatStartMessageInput = document.querySelector('[data-chat-start-message]');
        const chatStartShell = chatApp?.querySelector('[data-chat-start-shell]') || null;
        const chatComposeMediaPreview = chatCompose?.querySelector('[data-chat-media-preview]') || null;
        const chatStartMediaPreview = chatStartForm?.querySelector('[data-chat-media-preview]') || null;
        const bubbleHiddenKey = 'digihome-chat-bubble-hidden';
        const currentChatPath = chatFloatShell?.getAttribute('data-chat-path') || window.location.pathname;
        const allRecipientOptions = chatRecipientSelect
            ? Array.from(chatRecipientSelect.querySelectorAll('option[value]')).filter((option) => option.value !== '').map((option) => ({
                value: option.value,
                role: option.getAttribute('data-role') || '',
                email: option.getAttribute('data-email') || '',
                phone: option.getAttribute('data-phone') || '',
                label: option.getAttribute('data-label') || option.textContent || '',
            }))
            : [];

        let activeConversationId = Number.parseInt(chatConversationInput?.value || '0', 10) || 0;
        let unreadCount = 0;
        let adminOnline = false;
        let typingState = false;
        let typingHandle = null;
        let requestSerial = 0;
        let latestAppliedSerial = 0;
        let bubbleHidden = false;
        let activeChatPath = currentChatPath;
        let latestConversationState = null;
        let composeSendPending = false;
        let closePanelInFlight = false;
        let mobileThreadOpen = activeConversationId > 0;
        let startConversationFormOpen = false;
        let chatBusyDepth = 0;
        let chatBusyOverlay = null;
        let chatBusyLabel = null;

        const isMobileChatViewport = () => window.matchMedia('(max-width: 760px)').matches;

        const ensureChatBusyOverlay = () => {
            if (!(chatApp instanceof HTMLElement)) {
                return null;
            }
            if (chatBusyOverlay) {
                return chatBusyOverlay;
            }

            const overlay = document.createElement('div');
            overlay.className = 'chat-busy-overlay';
            overlay.hidden = true;
            overlay.setAttribute('role', 'status');
            overlay.setAttribute('aria-live', 'polite');

            const card = document.createElement('div');
            card.className = 'chat-busy-card';

            const spinner = document.createElement('span');
            spinner.className = 'chat-busy-spinner';
            spinner.setAttribute('aria-hidden', 'true');

            const label = document.createElement('p');
            label.className = 'chat-busy-label';
            label.textContent = 'Working...';

            card.appendChild(spinner);
            card.appendChild(label);
            overlay.appendChild(card);
            document.body.appendChild(overlay);

            chatBusyOverlay = overlay;
            chatBusyLabel = label;
            return overlay;
        };

        const showChatBusy = (labelText = 'Working...') => {
            const overlay = ensureChatBusyOverlay();
            if (!overlay) {
                return;
            }
            chatBusyDepth += 1;
            if (chatBusyLabel) {
                chatBusyLabel.textContent = labelText;
            }
            overlay.hidden = false;
            window.requestAnimationFrame(() => {
                overlay.classList.add('is-visible');
            });
        };

        const hideChatBusy = () => {
            if (chatBusyDepth > 0) {
                chatBusyDepth -= 1;
            }
            if (chatBusyDepth > 0 || !chatBusyOverlay) {
                return;
            }
            chatBusyOverlay.classList.remove('is-visible');
            window.setTimeout(() => {
                if (chatBusyDepth === 0 && chatBusyOverlay) {
                    chatBusyOverlay.hidden = true;
                }
            }, 170);
        };

        const runWithChatBusy = async (labelText, work) => {
            if (!(chatApp instanceof HTMLElement)) {
                return work();
            }
            showChatBusy(labelText);
            try {
                return await work();
            } finally {
                hideChatBusy();
            }
        };

        const setMobileThreadOpen = (isOpen) => {
            mobileThreadOpen = Boolean(isOpen);
            if (!chatApp) {
                return;
            }
            if (!isMobileChatViewport()) {
                chatApp.classList.remove('chat-mobile-thread-open');
                updateComposeState();
                return;
            }
            chatApp.classList.toggle('chat-mobile-thread-open', mobileThreadOpen);
            updateComposeState();
        };

        const setStartConversationFormOpen = (isOpen) => {
            startConversationFormOpen = Boolean(isOpen);
            chatApp?.classList.toggle('chat-start-form-open', startConversationFormOpen);
            if (!(chatStartShell instanceof HTMLElement)) {
                updateComposeState();
                return;
            }
            chatStartShell.hidden = !startConversationFormOpen;
            updateComposeState();
        };

        try {
            bubbleHidden = window.localStorage.getItem(bubbleHiddenKey) === '1';
        } catch (error) {
            bubbleHidden = false;
        }

        const syncChatNavBadges = (count) => {
            const badgeText = count > 99 ? '99+' : String(count);
            navChatLinks.forEach((link) => {
                let badge = link.querySelector('[data-chat-nav-badge]');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'chat-nav-badge';
                    badge.dataset.chatNavBadge = 'true';
                    link.appendChild(badge);
                }
                badge.hidden = count <= 0;
                badge.textContent = count > 0 ? badgeText : '';
            });

            if (chatFloatBadge) {
                chatFloatBadge.hidden = count <= 0;
                chatFloatBadge.textContent = count > 0 ? badgeText : '';
            }
        };

        const syncBubbleState = (count, onlineState) => {
            if (chatFloatStatus) {
                const showOnline = onlineState;
                chatFloatStatus.textContent = showOnline ? 'Online' : 'Offline';
                chatFloatStatus.classList.toggle('is-online', showOnline);
            }

            if (count > 0 && bubbleHidden) {
                bubbleHidden = false;
                try {
                    window.localStorage.setItem(bubbleHiddenKey, '0');
                } catch (error) {
                    // Ignore storage failures.
                }
            }

            chatFloatShell?.classList.toggle('is-hidden', count <= 0 && bubbleHidden);
        };

        const syncPageStatus = (onlineState) => {
            if (!chatPageStatus) {
                return;
            }
            chatPageStatus.classList.toggle('is-online', onlineState);
            chatPageStatus.classList.toggle('is-offline', !onlineState);
            const strong = chatPageStatus.querySelector('strong');
            if (strong) {
                strong.textContent = onlineState ? 'online' : 'offline';
            }
        };

        const updateComposeState = () => {
            const isAdminWithoutConversation = chatRole === 'admin' && activeConversationId <= 0;
            const shouldShowCloseButton = activeConversationId > 0
                || (chatRole === 'admin' && startConversationFormOpen)
                || (chatRole !== 'admin' && isMobileChatViewport() && mobileThreadOpen);

            if (chatCompose && chatMessageInput) {
                const submitButton = chatCompose.querySelector('button[type="submit"]');
                const mediaInput = chatCompose.querySelector('[data-chat-media-input]');
                const attachControl = chatCompose.querySelector('[data-chat-attach-control]');
                chatMessageInput.disabled = isAdminWithoutConversation;
                chatMessageInput.required = false;
                chatMessageInput.placeholder = isAdminWithoutConversation ? '' : (chatRole === 'admin' ? 'Reply to this conversation...' : 'Type your message...');
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = isAdminWithoutConversation;
                }
                if (mediaInput instanceof HTMLInputElement) {
                    mediaInput.disabled = isAdminWithoutConversation;
                }
                if (attachControl instanceof HTMLElement) {
                    attachControl.classList.toggle('is-disabled', isAdminWithoutConversation);
                }
            }

            chatClosePanelButtons.forEach((button) => {
                if (button instanceof HTMLButtonElement && button.classList.contains('chat-panel-close')) {
                    button.hidden = !shouldShowCloseButton;
                }
            });

            if (chatConversationMeta instanceof HTMLElement) {
                chatConversationMeta.hidden = activeConversationId <= 0;
            }
        };

        const setActiveConversation = (conversationId) => {
            activeConversationId = Number.parseInt(String(conversationId || 0), 10) || 0;
            chatApp?.classList.toggle('chat-no-active-conversation', activeConversationId <= 0);
            if (chatConversationInput) {
                chatConversationInput.value = String(activeConversationId);
            }
            if (chatList) {
                chatList.querySelectorAll('[data-chat-conversation-link]').forEach((link) => {
                    const linkConversationId = Number.parseInt(link.getAttribute('data-conversation-id') || '0', 10) || 0;
                    link.querySelector('.chat-item')?.classList.toggle('is-active', linkConversationId === activeConversationId);
                });
            }
            updateComposeState();
            if (activeConversationId > 0) {
                setStartConversationFormOpen(false);
            }
        };

        const renderMultilineText = (parent, text) => {
            const lines = String(text || '').split(/\r?\n/);
            lines.forEach((line, index) => {
                if (index > 0) {
                    parent.appendChild(document.createElement('br'));
                }
                parent.appendChild(document.createTextNode(line));
            });
        };

        const clearMediaPreview = (container) => {
            if (!container) {
                return;
            }
            container.innerHTML = '';
            container.hidden = true;
        };

        const rebuildFileInput = (inputElement, files) => {
            const dataTransfer = new DataTransfer();
            files.forEach((file) => dataTransfer.items.add(file));
            inputElement.files = dataTransfer.files;
        };

        const buildMediaPreviewNode = (file, inputElement, previewContainer) => {
            const previewWrap = document.createElement('div');
            previewWrap.className = 'chat-media-preview-card';

            const mimeType = String(file?.type || '');
            if (mimeType.startsWith('image/')) {
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = file.name || 'Selected image';
                image.dataset.objectUrlPreview = 'true';
                previewWrap.appendChild(image);
            } else if (mimeType.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                video.controls = true;
                video.preload = 'metadata';
                video.dataset.objectUrlPreview = 'true';
                previewWrap.appendChild(video);
            } else {
                const fileMeta = document.createElement('div');
                fileMeta.className = 'chat-media-file-card';
                const icon = document.createElement('i');
                icon.className = 'fa-regular fa-file-pdf';
                icon.setAttribute('aria-hidden', 'true');
                const label = document.createElement('span');
                label.textContent = file.name || 'Selected file';
                fileMeta.appendChild(icon);
                fileMeta.appendChild(label);
                previewWrap.appendChild(fileMeta);
            }

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'chat-media-preview-remove';
            removeButton.setAttribute('aria-label', `Remove ${file.name || 'file'}`);
            removeButton.textContent = '×';
            removeButton.addEventListener('click', () => {
                if (!(inputElement instanceof HTMLInputElement)) {
                    return;
                }
                const remainingFiles = Array.from(inputElement.files || []).filter((existingFile) => existingFile !== file);
                rebuildFileInput(inputElement, remainingFiles);
                renderMediaPreview(inputElement, previewContainer);
            });
            previewWrap.appendChild(removeButton);

            const caption = document.createElement('small');
            caption.className = 'chat-media-preview-name';
            caption.textContent = file.name || '';
            previewWrap.appendChild(caption);

            return previewWrap;
        };

        const renderMediaPreview = (inputElement, previewContainer) => {
            if (!(inputElement instanceof HTMLInputElement)) {
                return;
            }
            const selectedFiles = inputElement.files ? Array.from(inputElement.files) : [];
            if (selectedFiles.length === 0 || !previewContainer) {
                clearMediaPreview(previewContainer);
                return;
            }

            previewContainer.innerHTML = '';
            const grid = document.createElement('div');
            grid.className = 'chat-media-preview-grid';
            selectedFiles.forEach((file) => {
                grid.appendChild(buildMediaPreviewNode(file, inputElement, previewContainer));
            });
            previewContainer.appendChild(grid);
            previewContainer.hidden = false;
        };

        const bindMediaPreview = (formElement, previewContainer) => {
            if (!formElement) {
                return;
            }
            const mediaInput = formElement.querySelector('[data-chat-media-input]');
            if (!(mediaInput instanceof HTMLInputElement)) {
                return;
            }
            mediaInput.addEventListener('change', () => {
                renderMediaPreview(mediaInput, previewContainer);
            });
        };

        const formatRoleLabel = (roleValue) => {
            const normalized = String(roleValue || '').trim().toLowerCase();
            const map = {
                property_seeker: 'Property Seeker',
                property_owner: 'Property Owner',
                marketer: 'Marketer',
                admin: 'Admin',
            };
            if (map[normalized]) {
                return map[normalized];
            }
            return normalized
                .replaceAll('_', ' ')
                .split(' ')
                .filter((part) => part !== '')
                .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
                .join(' ');
        };

        const formatCompactRoleLabel = (roleValue) => {
            const normalized = String(roleValue || '').trim().toLowerCase();
            const map = {
                property_seeker: 'Seeker',
                property_owner: 'Owner',
                marketer: 'Marketer',
                admin: 'Admin',
            };
            return map[normalized] || formatRoleLabel(roleValue || 'User');
        };

        const composePersonLabel = (roleValue, firstName, lastName, fallbackName = 'User') => {
            const cleanFirst = String(firstName || '').trim();
            const cleanLast = String(lastName || '').trim();
            const personName = `${cleanFirst} ${cleanLast}`.trim() || fallbackName;
            const roleLabel = formatCompactRoleLabel(roleValue || '');
            return `${roleLabel}: ${personName}`;
        };

        const conversationRoute = (conversation) => {
            const scope = String(conversation?.conversation_scope || 'support').toLowerCase();
            const fromLabel = composePersonLabel(
                conversation?.requester_role || '',
                conversation?.requester_first_name || conversation?.first_name || '',
                conversation?.requester_last_name || conversation?.last_name || '',
                'User'
            );

            const recipientFirst = String(conversation?.recipient_first_name || '').trim();
            const recipientLast = String(conversation?.recipient_last_name || '').trim();
            const recipientName = `${recipientFirst} ${recipientLast}`.trim();
            const assignedFirst = String(conversation?.assigned_first_name || '').trim();
            const assignedLast = String(conversation?.assigned_last_name || '').trim();
            const assignedName = `${assignedFirst} ${assignedLast}`.trim();

            if (scope === 'support') {
                return {
                    from: fromLabel,
                    to: assignedName !== '' ? `Admin: ${assignedName} (Assigned)` : 'Admin Team (Unassigned)',
                };
            }

            if (scope === 'admin_broadcast') {
                return {
                    from: fromLabel,
                    to: recipientName !== '' ? `Admin: ${recipientName} (Broadcast)` : 'Admin Team (Broadcast)',
                };
            }

            return {
                from: fromLabel,
                to: recipientName !== ''
                    ? `${formatCompactRoleLabel(conversation?.recipient_role || 'admin')}: ${recipientName}`
                    : formatCompactRoleLabel(conversation?.recipient_role || 'admin'),
            };
        };

        const renderEmptyThread = () => {
            if (!chatThread) {
                return;
            }
            chatThread.innerHTML = '';
            const emptyState = document.createElement('div');
            emptyState.className = 'chat-empty-state';
            const icon = document.createElement('i');
            icon.className = 'fa-regular fa-comments';
            icon.setAttribute('aria-hidden', 'true');
            const paragraph = document.createElement('p');
            paragraph.textContent = 'Select a conversation or send a new message to start one.';
            emptyState.appendChild(icon);
            emptyState.appendChild(paragraph);

            if (chatRole === 'admin' && chatStartForm) {
                const startButton = document.createElement('button');
                startButton.type = 'button';
                startButton.className = 'chat-start-open';
                startButton.dataset.chatStartOpen = 'true';
                startButton.textContent = 'New message';
                emptyState.appendChild(startButton);
            }

            chatThread.appendChild(emptyState);

            if (chatRole === 'admin' && chatStartShell instanceof HTMLElement) {
                chatThread.appendChild(chatStartShell);
                setStartConversationFormOpen(startConversationFormOpen);
            }
        };

        const buildMediaAttachmentNode = (attachment) => {
            const mediaWrap = document.createElement('div');
            mediaWrap.className = 'chat-media';
            const mediaPath = String(attachment.media_path || attachment.path || '');
            const mediaName = String(attachment.media_name || attachment.name || 'attachment');
            const mediaType = String(attachment.media_type || attachment.type || '');
            if (mediaType.startsWith('image/')) {
                const image = document.createElement('img');
                image.src = mediaPath;
                image.alt = mediaName || 'Shared image';
                image.loading = 'lazy';
                mediaWrap.appendChild(image);
            } else if (mediaType.startsWith('video/')) {
                const fileMeta = document.createElement('div');
                fileMeta.className = 'chat-media-file-card chat-media-video-card';
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-file-video';
                icon.setAttribute('aria-hidden', 'true');
                const info = document.createElement('span');
                info.className = 'chat-media-video-info';
                const label = document.createElement('strong');
                label.textContent = 'Video';
                const nameLine = document.createElement('small');
                nameLine.textContent = mediaName;
                info.appendChild(label);
                info.appendChild(nameLine);
                fileMeta.appendChild(icon);
                fileMeta.appendChild(info);
                mediaWrap.appendChild(fileMeta);
            } else {
                const fileMeta = document.createElement('div');
                fileMeta.className = 'chat-media-file-card';
                const icon = document.createElement('i');
                icon.className = 'fa-regular fa-file';
                icon.setAttribute('aria-hidden', 'true');
                const label = document.createElement('span');
                label.textContent = mediaName;
                fileMeta.appendChild(icon);
                fileMeta.appendChild(label);
                mediaWrap.appendChild(fileMeta);
            }

            const downloadLink = document.createElement('a');
            downloadLink.className = 'chat-media-download';
            downloadLink.href = mediaPath;
            downloadLink.download = mediaName;
            downloadLink.target = '_blank';
            downloadLink.rel = 'noopener noreferrer';
            downloadLink.textContent = 'Download';
            mediaWrap.appendChild(downloadLink);

            return mediaWrap;
        };

        const fitSystemEventText = (textEl) => {
            if (!(textEl instanceof HTMLElement)) {
                return;
            }
            const maxFontSize = 13;
            const minFontSize = 8;
            let fontSize = maxFontSize;
            textEl.style.fontSize = `${fontSize}px`;
            while (textEl.scrollWidth > textEl.clientWidth && fontSize > minFontSize) {
                fontSize -= 0.5;
                textEl.style.fontSize = `${fontSize}px`;
            }
        };

        const fitAllSystemEventTexts = (root) => {
            const scope = root instanceof HTMLElement ? root : document;
            scope.querySelectorAll('.chat-system-event-text').forEach((el) => fitSystemEventText(el));
        };

        const buildSystemEventNode = (message) => {
            const wrap = document.createElement('div');
            wrap.className = 'chat-system-event';
            wrap.dataset.chatSystemEvent = 'true';
            if (message.id) {
                wrap.dataset.messageId = String(message.id);
            }

            const lineTop = document.createElement('div');
            lineTop.className = 'chat-system-event-line';
            lineTop.setAttribute('aria-hidden', 'true');

            const content = document.createElement('div');
            content.className = 'chat-system-event-content';

            const text = document.createElement('div');
            text.className = 'chat-system-event-text';
            text.textContent = message.message_body || '';

            const meta = document.createElement('div');
            meta.className = 'chat-system-event-meta';
            meta.textContent = message.created_at || '';

            content.appendChild(text);
            content.appendChild(meta);

            const lineBottom = document.createElement('div');
            lineBottom.className = 'chat-system-event-line';
            lineBottom.setAttribute('aria-hidden', 'true');

            wrap.appendChild(lineTop);
            wrap.appendChild(content);
            wrap.appendChild(lineBottom);
            return wrap;
        };

        const buildMessageBubble = (message) => {
            const bubble = document.createElement('div');
            const isMyUser = Number.parseInt(message.sender_user_id || '0', 10) === chatUserId;
            const isAdminMessage = String(message.sender_role || '').toLowerCase() === 'admin';
            const mine = chatRole === 'admin' ? isAdminMessage : isMyUser;
            bubble.className = `chat-bubble ${mine ? 'mine' : 'other'}`;
            if (message.id) {
                bubble.dataset.messageId = String(message.id);
            }

            const attachments = Array.isArray(message.attachments) ? message.attachments : [];
            const seenMediaPaths = new Set();
            if (message.media_path) {
                const primaryPath = String(message.media_path || '');
                if (primaryPath !== '') {
                    seenMediaPaths.add(primaryPath);
                    bubble.appendChild(buildMediaAttachmentNode({
                        media_path: primaryPath,
                        media_name: message.media_name,
                        media_type: message.media_type,
                    }));
                }
            }
            attachments.forEach((attachment) => {
                const attachmentPath = String(attachment.media_path || attachment.path || '');
                if (attachmentPath === '' || seenMediaPaths.has(attachmentPath)) {
                    return;
                }
                seenMediaPaths.add(attachmentPath);
                bubble.appendChild(buildMediaAttachmentNode(attachment));
            });

            const paragraph = document.createElement('p');
            if ((message.message_body || '').trim() !== '') {
                renderMultilineText(paragraph, message.message_body || '');
                bubble.appendChild(paragraph);
            }

            const meta = document.createElement('div');
            meta.className = 'chat-meta';

            const time = document.createElement('span');
            const editedSuffix = message.edited_at ? ' • edited' : '';
            time.textContent = `${message.created_at || ''}${editedSuffix}`;
            meta.appendChild(time);

            if (isMyUser) {
                const actions = document.createElement('span');
                actions.className = 'chat-meta-actions';

                const editButton = document.createElement('button');
                editButton.type = 'button';
                editButton.className = 'chat-action-text';
                editButton.dataset.chatEditMessage = String(message.id || 0);
                editButton.textContent = 'Edit';
                actions.appendChild(editButton);

                const tick = document.createElement('span');
                const isRead = Boolean(message.read_at);
                const isDelivered = Boolean(message.delivered_at);
                tick.className = `chat-tick ${isRead ? 'is-read' : (isDelivered ? 'is-delivered' : 'is-sent')}`;
                tick.dataset.chatTick = 'true';
                tick.textContent = isRead || isDelivered ? '✔✔' : '✔';
                actions.appendChild(tick);

                meta.appendChild(actions);
            }

            bubble.appendChild(meta);
            return bubble;
        };

        const scrollThreadToBottom = () => {
            if (!chatThread) {
                return;
            }
            chatThread.scrollTop = chatThread.scrollHeight;
        };

        const scrollThreadToTop = () => {
            if (!chatThread) {
                return;
            }
            chatThread.scrollTop = 0;
        };

        const messageSignature = (message) => JSON.stringify([
            message.message_body,
            message.edited_at,
            message.read_at,
            message.delivered_at,
            message.media_path,
            message.media_type,
            message.media_name,
            Array.isArray(message.attachments) ? message.attachments.length : 0,
            message.is_system_event,
        ]);

        const renderMessages = (messages) => {
            if (!chatThread) {
                return;
            }
            if (!messages || messages.length === 0) {
                chatThread.innerHTML = '';
                renderEmptyThread();
                return;
            }
            if (chatThread.querySelector('.chat-empty-state')) {
                chatThread.innerHTML = '';
            }

            const previousScrollTop = chatThread.scrollTop;
            const previousScrollHeight = chatThread.scrollHeight;
            const previousClientHeight = chatThread.clientHeight;
            const wasNearBottom = (previousScrollHeight - (previousScrollTop + previousClientHeight)) <= 60;

            // Reconcile by message id instead of wiping the thread every poll: rebuilding
            // every node (including already-loaded images) each cycle caused layout
            // thrash that made the thread appear to keep scrolling on its own.
            const controls = chatThread.querySelector('.chat-scroll-controls');
            const existingNodes = new Map();
            chatThread.querySelectorAll('[data-message-id]').forEach((node) => {
                const nodeId = node.getAttribute('data-message-id');
                if (nodeId) {
                    existingNodes.set(nodeId, node);
                }
            });

            const validIds = new Set();
            const changedSystemEventNodes = [];

            messages.forEach((message) => {
                const id = String(message.id || '');
                if (id === '' || id === '0') {
                    return;
                }
                validIds.add(id);
                const signature = messageSignature(message);
                const existingNode = existingNodes.get(id);

                if (existingNode && existingNode.dataset.messageSignature === signature) {
                    return;
                }

                const isSystemEvent = Number(message.is_system_event) === 1;
                const node = isSystemEvent ? buildSystemEventNode(message) : buildMessageBubble(message);
                node.dataset.messageSignature = signature;

                if (existingNode) {
                    existingNode.replaceWith(node);
                } else {
                    chatThread.insertBefore(node, controls || null);
                }

                if (isSystemEvent) {
                    changedSystemEventNodes.push(node);
                }
            });

            existingNodes.forEach((node, id) => {
                if (!validIds.has(id)) {
                    node.remove();
                }
            });

            if (wasNearBottom) {
                chatThread.scrollTop = chatThread.scrollHeight;
            }

            changedSystemEventNodes.forEach((node) => {
                fitSystemEventText(node.querySelector('.chat-system-event-text'));
            });
            renderScrollControls();
        };

        const appendPendingMessage = (messageBody) => {
            if (!chatThread) {
                return null;
            }

            const emptyState = chatThread.querySelector('.chat-empty-state');
            if (emptyState) {
                chatThread.innerHTML = '';
            }

            const bubble = document.createElement('div');
            bubble.className = 'chat-bubble mine is-pending';

            const mediaInput = chatCompose?.querySelector('[data-chat-media-input]');
            if (mediaInput instanceof HTMLInputElement && mediaInput.files && mediaInput.files.length > 0) {
                const selectedFiles = Array.from(mediaInput.files);
                selectedFiles.forEach((selectedFile) => {
                    const mediaWrap = document.createElement('div');
                    mediaWrap.className = 'chat-media chat-media-pending';
                    const mimeType = String(selectedFile.type || '');
                    if (mimeType.startsWith('image/')) {
                        const image = document.createElement('img');
                        image.src = URL.createObjectURL(selectedFile);
                        image.alt = selectedFile.name || 'Attachment preview';
                        image.dataset.objectUrlPreview = 'true';
                        mediaWrap.appendChild(image);
                    } else if (mimeType.startsWith('video/')) {
                        const fileMeta = document.createElement('div');
                        fileMeta.className = 'chat-media-file-card chat-media-video-card';
                        const icon = document.createElement('i');
                        icon.className = 'fa-solid fa-file-video';
                        icon.setAttribute('aria-hidden', 'true');
                        const info = document.createElement('span');
                        info.className = 'chat-media-video-info';
                        const label = document.createElement('strong');
                        label.textContent = 'Video';
                        const nameLine = document.createElement('small');
                        nameLine.textContent = selectedFile.name || 'Attachment';
                        info.appendChild(label);
                        info.appendChild(nameLine);
                        fileMeta.appendChild(icon);
                        fileMeta.appendChild(info);
                        mediaWrap.appendChild(fileMeta);
                    } else {
                        const fileMeta = document.createElement('div');
                        fileMeta.className = 'chat-media-file-card';
                        const icon = document.createElement('i');
                        icon.className = 'fa-regular fa-file-pdf';
                        icon.setAttribute('aria-hidden', 'true');
                        const label = document.createElement('span');
                        label.textContent = selectedFile.name || 'Attachment';
                        fileMeta.appendChild(icon);
                        fileMeta.appendChild(label);
                        mediaWrap.appendChild(fileMeta);
                    }
                    bubble.appendChild(mediaWrap);
                });
            }

            const paragraph = document.createElement('p');
            if ((messageBody || '').trim() !== '') {
                renderMultilineText(paragraph, messageBody);
                bubble.appendChild(paragraph);
            }

            const meta = document.createElement('div');
            meta.className = 'chat-meta';

            const sending = document.createElement('span');
            sending.textContent = 'Sending...';
            meta.appendChild(sending);

            const tick = document.createElement('span');
            tick.className = 'chat-tick is-sent';
            tick.textContent = '✔';
            meta.appendChild(tick);

            bubble.appendChild(meta);
            const controls = chatThread.querySelector('.chat-scroll-controls');
            chatThread.insertBefore(bubble, controls || null);
            chatThread.scrollTop = chatThread.scrollHeight;
            return bubble;
        };

        const setPeerTypingIndicator = (isTyping) => {
            if (!(chatPeerTyping instanceof HTMLElement)) {
                return;
            }
            chatPeerTyping.hidden = !isTyping;
        };

        const renderTyping = (typingStateData) => {
            const isTyping = Boolean(typingStateData && typingStateData.label);
            setPeerTypingIndicator(isTyping);

            if (!chatTyping) {
                return;
            }
            if (isTyping) {
                chatTyping.innerHTML = '';
                chatTyping.classList.add('is-active');

                const label = document.createElement('span');
                label.className = 'chat-typing-label';
                label.textContent = typingStateData.label || 'typing';

                const dots = document.createElement('span');
                dots.className = 'chat-typing-dots';
                dots.setAttribute('aria-hidden', 'true');
                for (let i = 0; i < 3; i += 1) {
                    const dot = document.createElement('i');
                    dot.className = 'chat-typing-dot';
                    dots.appendChild(dot);
                }

                chatTyping.appendChild(dots);
                chatTyping.appendChild(label);
            } else {
                chatTyping.innerHTML = '';
                chatTyping.classList.remove('is-active');
            }
        };

        const scopeLabel = (conversation) => {
            const scope = conversation?.conversation_scope || 'support';
            if (scope === 'admin_direct') {
                return 'Direct message from another admin';
            }
            if (scope === 'admin_broadcast') {
                return 'Visible to all admins';
            }
            if (scope === 'direct') {
                return 'Direct message';
            }
            return 'Support conversation';
        };

        const resolveAdminPeerPresence = (conversation) => {
            if (!conversation || chatRole !== 'admin') {
                return null;
            }

            const scope = String(conversation.conversation_scope || 'support').toLowerCase();
            const requesterId = Number.parseInt(conversation.requester_user_id || '0', 10) || 0;
            const recipientId = Number.parseInt(conversation.recipient_user_id || '0', 10) || 0;

            const requesterFirst = String(conversation.requester_first_name || conversation.first_name || '').trim();
            const requesterLast = String(conversation.requester_last_name || conversation.last_name || '').trim();
            const requesterName = `${requesterFirst} ${requesterLast}`.trim() || `User #${requesterId || ''}`.trim();

            const recipientFirst = String(conversation.recipient_first_name || '').trim();
            const recipientLast = String(conversation.recipient_last_name || '').trim();
            const recipientName = `${recipientFirst} ${recipientLast}`.trim() || `User #${recipientId || ''}`.trim();

            const requesterAvatar = String(conversation.requester_profile_picture || '').trim();
            const recipientAvatar = String(conversation.recipient_profile_picture || '').trim();

            if (scope === 'support') {
                return {
                    text: `${formatCompactRoleLabel(conversation.requester_role || 'property_seeker')}: ${requesterName}`,
                    name: requesterName,
                    avatar: requesterAvatar,
                    online: Number.parseInt(conversation.requester_online || '0', 10) === 1,
                };
            }

            if (requesterId === chatUserId && recipientId > 0) {
                return {
                    text: `${formatCompactRoleLabel(conversation.recipient_role || 'admin')}: ${recipientName}`,
                    name: recipientName,
                    avatar: recipientAvatar,
                    online: Number.parseInt(conversation.recipient_online || '0', 10) === 1,
                };
            }

            if (recipientId === chatUserId && requesterId > 0) {
                return {
                    text: `${formatCompactRoleLabel(conversation.requester_role || 'admin')}: ${requesterName}`,
                    name: requesterName,
                    avatar: requesterAvatar,
                    online: Number.parseInt(conversation.requester_online || '0', 10) === 1,
                };
            }

            if (recipientId > 0) {
                return {
                    text: `${formatCompactRoleLabel(conversation.recipient_role || 'admin')}: ${recipientName}`,
                    name: recipientName,
                    avatar: recipientAvatar,
                    online: Number.parseInt(conversation.recipient_online || '0', 10) === 1,
                };
            }

            return {
                text: `${formatCompactRoleLabel(conversation.requester_role || 'admin')}: ${requesterName}`,
                name: requesterName,
                avatar: requesterAvatar,
                online: Number.parseInt(conversation.requester_online || '0', 10) === 1,
            };
        };

        const renderAssignedAdminPresence = (conversation) => {
            if (chatRole === 'admin' || !conversation || !nonAdminPeerBanner) {
                return;
            }

            const assignedAdminName = String(conversation.assigned_admin_name || '').trim();
            const assignedAdminOnline = Boolean(conversation.assigned_admin_online) || Number.parseInt(conversation.assigned_admin_online || '0', 10) === 1;
            const assignedAdminLastSeen = String(conversation.assigned_admin_last_seen || '').trim();
            const resolvedName = assignedAdminName !== '' ? assignedAdminName : 'Admin Team';
            const resolvedStatus = assignedAdminOnline
                ? 'Admin - online'
                : (assignedAdminLastSeen !== '' ? `Admin - last seen ${assignedAdminLastSeen}` : 'Admin - offline');

            if (nonAdminPeerBannerName) {
                nonAdminPeerBannerName.textContent = resolvedName;
            }
            if (nonAdminPeerBannerStatus) {
                nonAdminPeerBannerStatus.textContent = resolvedStatus;
            }
        };

        const renderAdminPeerPresence = (conversation) => {
            if (chatRole !== 'admin' || !conversation) {
                if (chatRecipientPresence instanceof HTMLElement) {
                    chatRecipientPresence.hidden = true;
                }
                if (chatPeerAvatar instanceof HTMLImageElement) {
                    chatPeerAvatar.hidden = true;
                }
                if (chatPeerName) {
                    chatPeerName.textContent = '';
                }
                if (chatPeerStatus) {
                    chatPeerStatus.textContent = '';
                }
                return;
            }

            const presence = resolveAdminPeerPresence(conversation);
            if (!presence || !presence.text) {
                if (chatRecipientPresence instanceof HTMLElement) {
                    chatRecipientPresence.hidden = true;
                }
                if (chatPeerAvatar instanceof HTMLImageElement) {
                    chatPeerAvatar.hidden = true;
                }
                if (chatPeerName) {
                    chatPeerName.textContent = '';
                }
                if (chatPeerStatus) {
                    chatPeerStatus.textContent = '';
                }
                return;
            }

            if (chatRecipientPresence instanceof HTMLElement) {
                chatRecipientPresence.hidden = false;
                chatRecipientPresence.textContent = `${presence.text} ${presence.online ? 'is online' : 'is offline'}`;
                chatRecipientPresence.classList.toggle('is-online', Boolean(presence.online));
                chatRecipientPresence.classList.toggle('is-offline', !presence.online);
            }

            if (chatPeerAvatar instanceof HTMLImageElement) {
                const avatarSrc = presence.avatar || '';
                chatPeerAvatar.hidden = avatarSrc === '';
                if (avatarSrc !== '') {
                    chatPeerAvatar.src = avatarSrc;
                }
            }
            if (chatPeerName) {
                chatPeerName.textContent = presence.name || '';
            }
            if (chatPeerStatus) {
                chatPeerStatus.textContent = presence.online ? 'Online' : 'Offline';
            }
        };

        const renderConversationList = (conversations, chatPath) => {
            if (!chatList) {
                return;
            }

            const heading = chatList.querySelector('h3');
            chatList.innerHTML = '';
            if (heading) {
                const headingWrap = document.createElement('div');
                headingWrap.className = 'chat-list-head';
                headingWrap.appendChild(heading);

                const newChatButton = document.createElement('button');
                newChatButton.type = 'button';
                newChatButton.className = 'chat-mobile-new-button';
                newChatButton.dataset.chatMobileNew = 'true';
                newChatButton.setAttribute('aria-label', 'Start new chat');
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-pen-to-square';
                icon.setAttribute('aria-hidden', 'true');
                newChatButton.appendChild(icon);
                headingWrap.appendChild(newChatButton);

                chatList.appendChild(headingWrap);
            }

            if (!conversations || conversations.length === 0) {
                const paragraph = document.createElement('p');
                paragraph.textContent = chatRole === 'admin' ? 'No conversations yet.' : 'No conversations yet. Send a message to start one.';
                chatList.appendChild(paragraph);
                return;
            }

            conversations.forEach((conversation) => {
                const link = document.createElement('a');
                link.className = 'chat-item-link';
                link.href = `${chatPath}?id=${conversation.id}`;
                link.dataset.chatConversationLink = 'true';
                link.dataset.conversationId = String(conversation.id || 0);

                const item = document.createElement('div');
                const isDelayed = Number.parseInt(conversation.is_delayed || '0', 10) === 1;
                const statusValue = String(conversation.status || 'open').toLowerCase();
                item.className = `chat-item ${Number.parseInt(conversation.id || '0', 10) === activeConversationId ? 'is-active' : ''}${isDelayed ? ' is-delayed' : ''}${statusValue === 'closed' ? ' is-closed' : ''}`;

                const route = conversationRoute(conversation);
                const head = document.createElement('div');
                head.className = 'chat-item-head';

                const title = document.createElement('strong');
                title.className = 'chat-item-id';
                title.textContent = `#${conversation.id}`;
                head.appendChild(title);

                const status = document.createElement('span');
                status.className = 'chat-item-status-chip';
                if (statusValue === 'closed') {
                    status.textContent = 'Closed';
                } else if (isDelayed) {
                    status.textContent = 'Open • Delayed';
                } else {
                    status.textContent = 'Open';
                }
                head.appendChild(status);
                item.appendChild(head);

                const routeWrap = document.createElement('div');
                routeWrap.className = 'chat-item-route';

                const fromLine = document.createElement('small');
                fromLine.className = 'chat-item-route-line';
                fromLine.textContent = `From: ${route.from}`;
                routeWrap.appendChild(fromLine);

                const toLine = document.createElement('small');
                toLine.className = 'chat-item-route-line';
                toLine.textContent = `To: ${route.to}`;
                routeWrap.appendChild(toLine);

                item.appendChild(routeWrap);

                const unreadCount = Number.parseInt(conversation.unread_count || '0', 10) || 0;
                if (unreadCount > 0) {
                    const unreadBadge = document.createElement('span');
                    unreadBadge.className = 'chat-item-unread-badge';
                    unreadBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
                    item.appendChild(unreadBadge);
                }

                const foot = document.createElement('div');
                foot.className = 'chat-item-foot';

                const updated = document.createElement('small');
                updated.textContent = conversation.updated_at || conversation.created_at || '';
                foot.appendChild(updated);

                if (chatRole === 'admin') {
                    const scope = document.createElement('small');
                    scope.textContent = scopeLabel(conversation);
                    foot.appendChild(scope);
                }

                item.appendChild(foot);

                link.appendChild(item);
                link.addEventListener('click', async (event) => {
                    if (chatRole === 'admin' || isMobileChatViewport()) {
                        return;
                    }
                    event.preventDefault();
                    const conversationId = Number.parseInt(link.dataset.conversationId || '0', 10) || 0;
                    setActiveConversation(conversationId);
                    setMobileThreadOpen(true);
                    await runWithChatBusy('Opening chat...', async () => {
                        await fetchChatState(conversationId, { pushUrl: true });
                    });
                });
                chatList.appendChild(link);
            });
        };

        const renderState = (chatState) => {
            if (!chatState) {
                return;
            }

            latestConversationState = chatState;
            unreadCount = Number.parseInt(chatState.unread_count || '0', 10) || 0;
            adminOnline = Boolean(chatState.admin_online);
            activeChatPath = chatState.chat_path || activeChatPath;
            syncChatNavBadges(unreadCount);
            syncBubbleState(unreadCount, adminOnline);
            syncPageStatus(adminOnline);
            renderConversationList(chatState.conversations || [], activeChatPath);

            if (!chatApp) {
                return;
            }

            if (chatScopeLabel && activeConversationId > 0 && chatState.conversation) {
                chatScopeLabel.textContent = scopeLabel(chatState.conversation);
            }
            if (activeConversationId > 0 && chatState.conversation) {
                renderAssignedAdminPresence(chatState.conversation);
            }
            renderAdminPeerPresence(activeConversationId > 0 ? chatState.conversation : null);

            if (activeConversationId > 0 && chatState.conversation && Number.parseInt(chatState.conversation.id || '0', 10) === activeConversationId) {
                renderMessages(chatState.messages || []);
                renderTyping(chatState.typing || null);
            } else if (activeConversationId <= 0) {
                const keepAdminStartFormStable = chatRole === 'admin' && startConversationFormOpen;
                if (!keepAdminStartFormStable) {
                    renderEmptyThread();
                }
                renderTyping(null);
            }
        };

        const fetchChatState = async (conversationId = activeConversationId, options = {}) => {
            const serial = ++requestSerial;
            const params = new URLSearchParams();
            params.set('action', 'state');
            if (chatAuthToken !== '') {
                params.set('chat_auth_token', chatAuthToken);
            }
            if (conversationId > 0) {
                params.set('conversation_id', String(conversationId));
            }
            const response = await fetch(`${chatStateUrl}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!payload.ok || serial < latestAppliedSerial) {
                return null;
            }
            latestAppliedSerial = serial;
            renderState(payload.chat || null);
            if (options.pushUrl) {
                const nextUrl = new URL(window.location.href);
                if (conversationId > 0) {
                    nextUrl.searchParams.set('id', String(conversationId));
                } else {
                    nextUrl.searchParams.delete('id');
                }
                window.history.pushState({ conversationId }, '', nextUrl.toString());
            }
            return payload;
        };

        const postChatForm = async (formElement, actionName, existingFormData = null) => {
            const formData = existingFormData instanceof FormData ? existingFormData : new FormData(formElement);
            formData.set('action', actionName);
            if (chatAuthToken !== '') {
                formData.set('chat_auth_token', chatAuthToken);
            }
            const response = await fetch(chatStateUrl, {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            return response.json();
        };

        const stopTyping = () => {
            if (!typingState || activeConversationId <= 0) {
                return;
            }
            typingState = false;
            const body = new FormData();
            body.set('action', 'typing');
            body.set('conversation_id', String(activeConversationId));
            body.set('is_typing', '0');
            if (chatAuthToken !== '') {
                body.set('chat_auth_token', chatAuthToken);
            }
            fetch(chatStateUrl, {
                method: 'POST',
                body,
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            }).catch(() => {});
        };

        const sendTyping = () => {
            if (activeConversationId <= 0) {
                return;
            }
            const body = new FormData();
            body.set('action', 'typing');
            body.set('conversation_id', String(activeConversationId));
            body.set('is_typing', '1');
            if (chatAuthToken !== '') {
                body.set('chat_auth_token', chatAuthToken);
            }
            fetch(chatStateUrl, {
                method: 'POST',
                body,
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            }).catch(() => {});
            typingState = true;
        };

        const handleEnterToSend = (textArea, submitter) => {
            textArea?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    submitter();
                }
            });
        };

        const populateRecipientOptions = () => {
            if (!chatRecipientSelect || !chatRoleFilter) {
                return;
            }
            const selectedRole = chatRoleFilter.value;
            chatRecipientSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = selectedRole === '' ? 'Select a role first' : 'Select a user';
            chatRecipientSelect.appendChild(placeholder);

            allRecipientOptions
                .filter((option) => selectedRole === '' || option.role === selectedRole)
                .forEach((option) => {
                    const node = document.createElement('option');
                    node.value = option.value;
                    node.textContent = option.label;
                    node.dataset.role = option.role;
                    node.dataset.email = option.email;
                    node.dataset.phone = option.phone;
                    chatRecipientSelect.appendChild(node);
                });

            if (chatVisibilitySelect) {
                const visibility = chatVisibilitySelect.value;
                chatVisibilitySelect.value = visibility === 'broadcast' ? 'broadcast' : 'direct';
            }

            if (chatRecipientRoleInput) {
                chatRecipientRoleInput.value = selectedRole;
            }
            if (chatRecipientMeta) {
                chatRecipientMeta.textContent = 'Select a person to view email and phone.';
            }
        };

        const syncRecipientMeta = () => {
            if (!chatRecipientSelect || !chatRecipientMeta || !chatRecipientRoleInput) {
                return;
            }
            const selected = chatRecipientSelect.selectedOptions[0];
            const role = selected?.getAttribute('data-role') || chatRoleFilter?.value || '';
            chatRecipientRoleInput.value = role;
            if (!selected || selected.value === '') {
                chatRecipientMeta.textContent = 'Select a person to view email and phone.';
                return;
            }
            const email = selected.getAttribute('data-email') || 'No email';
            const phone = selected.getAttribute('data-phone') || 'No phone';
            chatRecipientMeta.textContent = `${email} • ${phone}`;
        };

        const submitCompose = async () => {
            if (!chatCompose || !chatMessageInput || composeSendPending) {
                return;
            }
            composeSendPending = true;
            const submitButton = chatCompose.querySelector('button[type="submit"]');
            if (submitButton instanceof HTMLButtonElement) {
                submitButton.disabled = true;
                submitButton.classList.add('is-busy');
                submitButton.innerHTML = '<span>Sending...</span>';
            }
            const formData = new FormData(chatCompose);
            const outgoingMessage = chatMessageInput.value;
            const mediaInput = chatCompose.querySelector('[data-chat-media-input]');
            const hasMedia = mediaInput instanceof HTMLInputElement && mediaInput.files && mediaInput.files.length > 0;
            if (outgoingMessage.trim() === '' && !hasMedia) {
                composeSendPending = false;
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('is-busy');
                    submitButton.innerHTML = '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i>';
                }
                return;
            }
            if (activeConversationId <= 0 && chatRole !== 'admin') {
                formData.set('force_new', '1');
            }
            const pendingNode = appendPendingMessage(outgoingMessage);
            chatMessageInput.value = '';
            if (mediaInput instanceof HTMLInputElement) {
                mediaInput.value = '';
            }
            clearMediaPreview(chatComposeMediaPreview);
            try {
                await runWithChatBusy('Sending message...', async () => {
                    const payload = await postChatForm(chatCompose, 'send', formData);
                    if (!payload.ok) {
                        pendingNode?.remove();
                        chatMessageInput.value = outgoingMessage;
                        return;
                    }
                    pendingNode?.remove();
                    if (payload.conversation_id) {
                        setActiveConversation(payload.conversation_id);
                        setMobileThreadOpen(true);
                        if (payload.chat) {
                            renderState(payload.chat);
                            const nextUrl = new URL(window.location.href);
                            nextUrl.searchParams.set('id', String(payload.conversation_id));
                            window.history.pushState({ conversationId: payload.conversation_id }, '', nextUrl.toString());
                        }
                        window.setTimeout(() => {
                            fetchChatState(payload.conversation_id, { pushUrl: false }).catch(() => {});
                        }, 80);
                    } else {
                        if (payload.chat) {
                            renderState(payload.chat);
                        } else {
                            fetchChatState(activeConversationId, { pushUrl: false }).catch(() => {});
                        }
                    }
                    chatMessageInput.focus();
                    stopTyping();
                });
            } finally {
                composeSendPending = false;
                if (submitButton instanceof HTMLButtonElement) {
                    submitButton.disabled = chatRole === 'admin' && activeConversationId <= 0;
                    submitButton.classList.remove('is-busy');
                    submitButton.innerHTML = '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i>';
                }
            }
        };

        const submitStartConversation = async () => {
            if (!chatStartForm) {
                return;
            }
            const startSubmitButton = chatStartForm.querySelector('button[type="submit"]');
            if (startSubmitButton instanceof HTMLButtonElement) {
                startSubmitButton.disabled = true;
                startSubmitButton.classList.add('is-busy');
                startSubmitButton.innerHTML = '<span>Sending...</span>';
            }
            const startMessageInput = chatStartForm.querySelector('[data-chat-start-message]');
            const startMediaInput = chatStartForm.querySelector('[data-chat-media-input]');
            const startMessage = startMessageInput instanceof HTMLTextAreaElement ? startMessageInput.value : '';
            const hasMedia = startMediaInput instanceof HTMLInputElement && startMediaInput.files && startMediaInput.files.length > 0;
            if (startMessage.trim() === '' && !hasMedia) {
                return;
            }
            await runWithChatBusy('Starting conversation...', async () => {
                const payload = await postChatForm(chatStartForm, 'start_conversation');
                if (!payload.ok) {
                    return;
                }
                if (payload.conversation_id) {
                    setActiveConversation(payload.conversation_id);
                    setMobileThreadOpen(true);
                    if (payload.chat) {
                        renderState(payload.chat);
                        const nextUrl = new URL(window.location.href);
                        nextUrl.searchParams.set('id', String(payload.conversation_id));
                        window.history.pushState({ conversationId: payload.conversation_id }, '', nextUrl.toString());
                    }
                    window.setTimeout(() => {
                        fetchChatState(payload.conversation_id, { pushUrl: false }).catch(() => {});
                    }, 80);
                }
                if (chatStartMessageInput) {
                    chatStartMessageInput.value = '';
                }
                if (startMediaInput instanceof HTMLInputElement) {
                    startMediaInput.value = '';
                }
                clearMediaPreview(chatStartMediaPreview);
                setStartConversationFormOpen(false);
            });
            if (startSubmitButton instanceof HTMLButtonElement) {
                startSubmitButton.disabled = false;
                startSubmitButton.classList.remove('is-busy');
                startSubmitButton.innerHTML = '<i class="fa-solid fa-paper-plane" aria-hidden="true"></i>';
            }
        };

        const editMessage = async (messageId, currentText) => {
            const nextText = window.prompt('Edit message', currentText);
            if (nextText === null) {
                return;
            }
            const body = new FormData();
            body.set('action', 'edit_message');
            body.set('message_id', String(messageId));
            body.set('message_body', nextText);
            if (chatAuthToken !== '') {
                body.set('chat_auth_token', chatAuthToken);
            }
            await runWithChatBusy('Updating message...', async () => {
                const response = await fetch(chatStateUrl, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                if (!payload.ok) {
                    return;
                }
                if (payload.chat) {
                    renderState(payload.chat);
                }
                fetchChatState(payload.conversation_id || activeConversationId, { pushUrl: false }).catch(() => {});
            });
        };

        chatFloatShell?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            if (target.closest('[data-chat-float-close]')) {
                event.preventDefault();
                event.stopPropagation();
                bubbleHidden = true;
                try {
                    window.localStorage.setItem(bubbleHiddenKey, '1');
                } catch (error) {
                    // Ignore storage failures.
                }
                syncBubbleState(unreadCount, adminOnline);
            }
        });

        chatThread?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const editButton = target.closest('[data-chat-edit-message]');
            if (!editButton) {
                return;
            }
            const bubble = editButton.closest('.chat-bubble');
            const text = bubble?.querySelector('p')?.textContent || '';
            const messageId = Number.parseInt(editButton.getAttribute('data-chat-edit-message') || '0', 10) || 0;
            if (messageId > 0) {
                editMessage(messageId, text).catch(() => {});
            }
        });

        const closeConversationPanel = () => {
            if (closePanelInFlight) {
                return;
            }
            closePanelInFlight = true;
            showChatBusy('Closing chat...');
            const hadActiveConversation = activeConversationId > 0;

            setActiveConversation(0);
            renderEmptyThread();
            renderTyping(null);
            setMobileThreadOpen(false);
            setStartConversationFormOpen(false);
            updateComposeState();

            if (chatRole === 'admin' && hadActiveConversation) {
                const resetUrl = new URL(window.location.href);
                resetUrl.searchParams.delete('id');
                window.location.replace(resetUrl.toString());
                return;
            }

            fetchChatState(0, { pushUrl: true })
                .catch(() => {})
                .finally(() => {
                    closePanelInFlight = false;
                    hideChatBusy();
                });
        };

        chatApp?.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof HTMLElement)) {
                return;
            }
            const mobileNewButton = target.closest('[data-chat-mobile-new]');
            if (mobileNewButton) {
                event.preventDefault();
                event.stopPropagation();
                setActiveConversation(0);
                renderEmptyThread();
                renderTyping(null);
                setMobileThreadOpen(true);
                if (chatRole === 'admin') {
                    setStartConversationFormOpen(true);
                    chatStartMessageInput?.focus();
                } else {
                    chatMessageInput?.focus();
                }
                return;
            }
            const startOpenButton = target.closest('[data-chat-start-open]');
            if (startOpenButton) {
                event.preventDefault();
                event.stopPropagation();
                setStartConversationFormOpen(true);
                chatStartMessageInput?.focus();
                return;
            }
            const closeButton = target.closest('[data-chat-close-panel]');
            if (!closeButton) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            closeConversationPanel();
        });

        const renderScrollControls = () => {
            if (!chatThread || chatThread.querySelector('.chat-scroll-controls')) {
                return;
            }
            const controls = document.createElement('div');
            controls.className = 'chat-scroll-controls';
            const upButton = document.createElement('button');
            upButton.type = 'button';
            upButton.className = 'chat-scroll-control';
            upButton.setAttribute('aria-label', 'Jump to top');
            upButton.innerHTML = '<i class="fa-solid fa-arrow-up" aria-hidden="true"></i>';
            upButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                scrollThreadToTop();
            });
            const downButton = document.createElement('button');
            downButton.type = 'button';
            downButton.className = 'chat-scroll-control';
            downButton.setAttribute('aria-label', 'Jump to bottom');
            downButton.innerHTML = '<i class="fa-solid fa-arrow-down" aria-hidden="true"></i>';
            downButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                scrollThreadToBottom();
            });
            controls.appendChild(upButton);
            controls.appendChild(downButton);
            chatThread.appendChild(controls);
        };

        chatCompose?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitCompose();
        });

        handleEnterToSend(chatMessageInput, () => {
            submitCompose().catch(() => {});
        });
        handleEnterToSend(chatStartMessageInput, () => {
            submitStartConversation().catch(() => {});
        });

        chatMessageInput?.addEventListener('input', () => {
            if ((chatMessageInput.value || '').trim() === '' || activeConversationId <= 0) {
                stopTyping();
                return;
            }
            sendTyping();
            window.clearTimeout(typingHandle);
            typingHandle = window.setTimeout(() => {
                stopTyping();
            }, 1200);
        });

        chatMessageInput?.addEventListener('blur', () => {
            window.clearTimeout(typingHandle);
            stopTyping();
        });

        chatStartForm?.addEventListener('submit', async (event) => {
            event.preventDefault();
            await submitStartConversation();
        });

        bindMediaPreview(chatCompose, chatComposeMediaPreview);
        bindMediaPreview(chatStartForm, chatStartMediaPreview);
        clearMediaPreview(chatComposeMediaPreview);
        clearMediaPreview(chatStartMediaPreview);
        renderScrollControls();
        fitAllSystemEventTexts(chatThread || document);

        chatRoleFilter?.addEventListener('change', populateRecipientOptions);
        chatRecipientSelect?.addEventListener('change', syncRecipientMeta);
        populateRecipientOptions();
        syncRecipientMeta();

        window.addEventListener('popstate', async (event) => {
            const stateConversationId = Number.parseInt(String(event.state?.conversationId || 0), 10) || 0;
            setActiveConversation(stateConversationId);
            setMobileThreadOpen(stateConversationId > 0);
            await fetchChatState(stateConversationId, { pushUrl: false });
        });

        window.addEventListener('resize', () => {
            setMobileThreadOpen(mobileThreadOpen);
            fitAllSystemEventTexts(chatThread || document);
        });

        setActiveConversation(activeConversationId);
        setMobileThreadOpen(activeConversationId > 0);
        setStartConversationFormOpen(false);
        fetchChatState(activeConversationId, { pushUrl: false }).catch(() => {});
        window.setInterval(() => {
            const hasActiveVideo = Array.from(document.querySelectorAll('.chat-media video')).some((video) => !video.paused && !video.ended);
            if (!hasActiveVideo) {
                fetchChatState(activeConversationId, { pushUrl: false }).catch(() => {});
            }
        }, 2000);
        window.addEventListener('beforeunload', stopTyping);
    }
});
