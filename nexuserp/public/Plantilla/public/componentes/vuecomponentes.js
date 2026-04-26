Vue.component('v-select', VueSelect.VueSelect);

Vue.component("select2", {
    props: ["value"],
    template: `<select style="width: 100%;"><slot></slot></select>`,
    mounted() {
        this.initSelect2();
    },
    watch: {
        value(newVal) {
            this.updateValue(newVal);
        },
        optionsHtml() {
            this.reinitSelect2();
        }
    },
    computed: {
        optionsHtml() {
            return this.$el ? this.$el.innerHTML : "";
        }
    },
    methods: {
        initSelect2() {
            const vm = this;
            const $el = $(this.$el);

            $el.select2({
                width: "100%",
                theme: "bootstrap-5",
                dropdownParent: $el.closest(".mb-3").length
                    ? $el.closest(".mb-3")
                    : $(document.body),
                dropdownAutoWidth: true
            });

            this.updateValue(this.value);

            $el.on("change", function () {
                let selected = $el.val();

                if (Array.isArray(selected) && !$el.prop("multiple")) {
                    selected = selected[0];
                }

                let emitValue = selected;
                if (selected === null || selected === "null") {
                    emitValue = null;
                } else if (selected === "") {
                    emitValue = "";
                } else if (!isNaN(selected) && Number(selected).toString() === selected) {
                    emitValue = Number(selected);
                }

                vm.$emit("input", emitValue);
            });
        },
        updateValue(val) {
            const $el = $(this.$el);
            this.$nextTick(() => {
                if (val === null || val === undefined || val === "") {
                    $el.val("").trigger("change.select2");
                } else {
                    $el.val(String(val)).trigger("change.select2");
                }
            });
        },
        reinitSelect2() {
            const $el = $(this.$el);
            $el.off("change.select2");
            if ($el.data("select2")) {
                $el.select2("destroy");
            }
            this.initSelect2();
        }
    },
    beforeDestroy() {
        const $el = $(this.$el);
        $el.off("change");
        if ($el.data("select2")) {
            $el.select2("destroy");
        }
    }
});

Vue.component("select3", {
    props: {
        value: [String, Number, null],
        options: { type: Array, required: true },
        placeholder: { type: String, default: "Seleccione un dato" },
        id: String,
        name: String,
        valueField: { type: String, default: "id" },
        labelField: { type: String, default: "name" },
        fetchOptions: Function,
        required: { type: Boolean, default: false },
        autovalidar: { type: Boolean, default: true },
        disabled: { type: Boolean, default: false }
    },
    template: `
        <div>
            <select
            :id="id"
            :name="name"
            :required="required"
            :disabled="disabled"
            @@invalid="onInvalid"
            @@input="onInput"
            @@change="onInput"
            style="width: 100%;"
            ></select>
            <div v-if="showError" class="invalid-feedback" style="display:block;color:#dc3545;margin-top:4px;">
            Este campo es obligatorio.
            </div>
        </div>
        `,
    data() {
        return {
            choicesInstance: null,
            showError: false
        };
    },
    mounted() {
        this.initChoices();
        this.$emit("register-validation", this);

        if (this.value === undefined) {
            this.$emit("input", null);
        }
    },
    watch: {
        value(newVal) {
            if (this.choicesInstance) {
                const current = this.choicesInstance.getValue(true);
                const valueStr = newVal != null ? String(newVal) : "";
                if (current !== valueStr) {
                    this.choicesInstance.removeActiveItems();
                    this.choicesInstance.setChoiceByValue(valueStr);
                }
            }
        },
        options: {
            handler() {
                this.refreshChoices();
            },
            deep: true
        }
    },
    methods: {
        initChoices() {
            const vm = this;
            const element = this.$el.querySelector("select");

            this.choicesInstance = new Choices(element, {
                removeItemButton: true,
                shouldSort: false,
                placeholder: true,
                placeholderValue: this.placeholder,
                itemSelectText: "Presione para seleccionar",
                searchEnabled: true,
                searchChoices: !this.fetchOptions,
                classNames: {
                    containerOuter: "choices select3-wrapper"
                }
            });

            this.refreshChoices();

            element.addEventListener("change", function () {
                const selectedValue = element.value;
                vm.$emit("input", selectedValue !== "" ? selectedValue : null);
            });

            element.addEventListener("search", async (e) => {
                const keyword = e.detail?.value || e.target.value;
                if (vm.fetchOptions && keyword.length >= 3) {
                    const results = await vm.fetchOptions(keyword);
                    vm.updateOptionsFromSearch(results);
                }
            });
        },
        refreshChoices() {
            if (!this.choicesInstance) return;

            const formattedOptions = this.formatOptions(this.options);

            this.choicesInstance.clearChoices();
            this.choicesInstance.setChoices(formattedOptions, "value", "label", true);
            this.choicesInstance.removeActiveItems();

            // Selección automática si solo hay un registro real
            if (this.options.length === 1) {
                const valueStr = String(this.options[0][this.valueField]);
                this.choicesInstance.setChoiceByValue(valueStr);
                if (this.value !== valueStr) {
                    this.$emit("input", valueStr);
                }
            } else {
                const valueStr = this.value != null ? String(this.value) : "";
                this.choicesInstance.setChoiceByValue(valueStr);
            }
        },
        updateOptionsFromSearch(newOptions) {
            if (!this.choicesInstance) return;

            const formattedOptions = this.formatOptions(newOptions);
            this.choicesInstance.clearChoices();
            this.choicesInstance.setChoices(formattedOptions, "value", "label", true);
            this.choicesInstance.removeActiveItems();

            if (newOptions.length === 1) {
                const valueStr = String(newOptions[0][this.valueField]);
                this.choicesInstance.setChoiceByValue(valueStr);
                if (this.value !== valueStr) {
                    this.$emit("input", valueStr);
                }
            } else {
                const valueStr = this.value != null ? String(this.value) : "";
                this.choicesInstance.setChoiceByValue(valueStr);
            }
        },
        formatOptions(options) {
            const isEmpty = this.value === null || this.value === undefined || this.value === "";

            const baseOption = {
                value: "",
                label: this.placeholder,
                disabled: true,
                selected: isEmpty
            };

            const formatted = options.map(opt => ({
                value: String(opt[this.valueField]),
                label: String(opt[this.labelField])
            }));

            return [baseOption, ...formatted];
        },
        onInvalid() {
            this.showError = true;
            this.$el.querySelector("select").classList.add("is-invalid");
        },
        onInput() {
            this.showError = false;
            this.$el.querySelector("select").classList.remove("is-invalid");
        },
        forceRefresh() {
            // Método público para forzar el refresco desde el componente padre
            this.refreshChoices();
        }
    },
    destroyed() {
        if (this.choicesInstance) {
            this.choicesInstance.destroy();
            this.choicesInstance = null;
        }
    }
});

Vue.component("base64-decode", {
    props: {
        value: {
            type: String,
            required: true
        },
        tag: {
            // Permite elegir el tag html (ej: span, div, p)
            type: String,
            default: "span"
        }
    },
    computed: {
        decoded() {
            try {
                // Decodifica base64 con soporte para UTF-8
                return decodeURIComponent(escape(window.atob(this.value)));
            } catch (e) {
                return "Error al decodificar";
            }
        }
    },
    render(createElement) {
        // Renderiza el tag deseado con el texto decodificado
        return createElement(this.tag, this.decoded);
    }
});
Vue.filter('base64decode', function(value) {
    if (!value) return '';
    try {
        return decodeURIComponent(escape(window.atob(value)));
    } catch (e) {
        return 'Error al decodificar';
    }
});

Vue.component("datepicker-vue1", {
    props: {
        value: String,
        id: String,
        name: String,
        placeholder: {
            type: String,
            default: "Seleccione una fecha"
        },
        required: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        dateFormat: {
            type: String,
            default: "Y-m-d"
        }
    },
    template: `
        <div>
            <input
                ref="datepicker"
                type="text"
                :id="id"
                :name="name"
                class="form-control"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
            />
        </div>
    `,
    data() {
        return {
            picker: null
        };
    },
    mounted() {
        const today = new Date();
        const initial = this.cleanDate(this.value);

        this.picker = flatpickr(this.$refs.datepicker, {
            dateFormat: this.dateFormat,
            altInput: true,
            altFormat: "d/m/Y",
            disableMobile: true,
            locale: flatpickr.l10ns.es,
            defaultDate: initial || today,
            onChange: this.onChange
        });

        // ✅ Estilo personalizado del campo
        if (this.picker.altInput) {
            Object.assign(this.picker.altInput.style, {
                backgroundColor: "#fdfefe",
                borderColor: "#ced4da",
                color: "#212529"
            });
        }

        // Emitir la fecha actual si no vino nada
        if (!this.value) {
            this.$emit("input", this.formatDate(today));
        }
    },
    methods: {
        onChange(selectedDates, dateStr) {
            this.$emit("input", dateStr);
        },
        cleanDate(dateStr) {
            // Transforma ISO string "2025-05-05T00:00:00" a "2025-05-05"
            if (!dateStr) return null;
            return dateStr.split("T")[0];
        },
        formatDate(dateObj) {
            const pad = n => (n < 10 ? "0" + n : n);
            return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}`;
        }
    },
    watch: {
        value(newVal) {
            const clean = this.cleanDate(newVal);
            if (clean && this.picker) {
                this.picker.setDate(clean, false);
            }
        }
    },
    beforeDestroy() {
        if (this.picker) {
            this.picker.destroy();
        }
    }
});

Vue.component("datepicker-vue", {
    props: {
        value: String,
        id: String,
        name: String,
        placeholder: {
            type: String,
            default: "Seleccione una fecha"
        },
        required: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        dateFormat: {
            type: String,
            default: "Y-m-d"
        }
    },
    template: `
        <div>
            <input
                ref="datepicker"
                type="text"
                :id="id"
                :name="name"
                class="form-control"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
            />
        </div>
    `,
    data() {
        return {
            picker: null
        };
    },
    mounted() {
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);

        const initial = this.cleanDate(this.value) || this.formatDate(today);

        this.picker = flatpickr(this.$refs.datepicker, {
            dateFormat: this.dateFormat,
            altInput: true,
            altFormat: "d/m/Y",
            disableMobile: true,
            locale: flatpickr.l10ns.es,
            defaultDate: initial,
            minDate: yesterday, // <-- permite ayer, hoy y futuro
            onChange: this.onChange
        });

        // Estilo personalizado del campo
        if (this.picker.altInput) {
            Object.assign(this.picker.altInput.style, {
                backgroundColor: "#fdfefe",
                borderColor: "#ced4da",
                color: "#212529"
            });
        }

        // Emitir la fecha actual si no vino nada
        if (!this.value) {
            this.$emit("input", this.formatDate(today));
        }
    },
    methods: {
        onChange(selectedDates, dateStr) {
            this.$emit("input", dateStr);
        },
        cleanDate(dateStr) {
            if (!dateStr) return null;
            return dateStr.split("T")[0];
        },
        formatDate(dateObj) {
            const pad = n => (n < 10 ? "0" + n : n);
            return `${dateObj.getFullYear()}-${pad(dateObj.getMonth() + 1)}-${pad(dateObj.getDate())}`;
        }
    },
    watch: {
        value(newVal) {
            const today = new Date();
            const clean = this.cleanDate(newVal);

            if (!clean) {
                // Si el valor es vacío/null, emitir el día actual y actualizar el flatpickr
                const todayStr = this.formatDate(today);
                this.$emit("input", todayStr);
                if (this.picker) {
                    this.picker.setDate(todayStr, false);
                }
            } else if (this.picker) {
                this.picker.setDate(clean, false);
            }
        }
    },
    beforeDestroy() {
        if (this.picker) {
            this.picker.destroy();
        }
    }
});

Vue.component("datepicker-rango", {
    props: {
        value: {
            type: String,
            default: ""
        },
        id: {
            type: String,
            default: "rango-fechas"
        },
        name: {
            type: String,
            default: "rango-fechas"
        },
        placeholder: {
            type: String,
            default: "Seleccione un rango de fechas"
        },
        required: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false
        },
        dateFormat: {
            type: String,
            default: "Y-m-d"
        }
    },
    template: `
        <div>
            <input
                ref="datepicker"
                type="text"
                :id="id"
                :name="name"
                class="form-control"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
            />
        </div>
    `,
    data() {
        return {
            picker: null
        };
    },
    mounted() {
        // Preparar fechas iniciales si existen, si no, poner las de hoy
        let start, end;
        if (this.value) {
            [start, end] = this.value.split(" - ");
        } else {
            // Tomar la fecha de hoy y construir el rango
            let hoy = new Date();
            let yyyy = hoy.getFullYear();
            let mm = String(hoy.getMonth() + 1).padStart(2, '0');
            let dd = String(hoy.getDate()).padStart(2, '0');
            let fechaHoy = `${yyyy}-${mm}-${dd}`;
            start = end = fechaHoy;
            // Emitir el valor inicial para que el padre lo reciba

            const hace7dias = new Date(hoy);
            hace7dias.setDate(hoy.getDate() - 365);
            const dd7 = String(hace7dias.getDate()).padStart(2, '0');
            const mm7 = String(hace7dias.getMonth() + 1).padStart(2, '0');
            const yyyy7 = hace7dias.getFullYear();
            const fechaHace7 = `${yyyy7}-${mm7}-${dd7}`;

            this.$emit("input", `${fechaHace7} - ${fechaHoy}`);
        }

        this.picker = flatpickr(this.$refs.datepicker, {
            mode: "range",
            dateFormat: this.dateFormat,      // formato para backend
            altInput: true,
            altFormat: "d/m/Y",               // formato visual
            disableMobile: true,
            locale: flatpickr.l10ns.es,
            defaultDate: [start, end].filter(Boolean),
            onChange: this.onChange
        });

        // Estilo personalizado del campo
        if (this.picker.altInput) {
            Object.assign(this.picker.altInput.style, {
                backgroundColor: "#fdfefe",
                borderColor: "#ced4da",
                color: "#212529"
            });
        }
    },
    methods: {
        onChange(selectedDates, dateStr) {
            // dateStr será "2025-07-24 to 2025-08-25"
            let fechas = dateStr.replace(" to ", " - ");
            this.$emit("input", fechas);
        }
    },
    watch: {
        value(newVal) {
            // Si cambia el modelo desde el padre, actualizar el flatpickr
            if (this.picker) {
                let [start, end] = (newVal || "").split(" - ");
                this.picker.setDate([start, end].filter(Boolean), false);
            }
        }
    },
    beforeDestroy() {
        if (this.picker) {
            this.picker.destroy();
        }
    }
});

Vue.component("select4", {
    props: {
        value: [String, Number, null],
        options: { type: Array, required: true },
        placeholder: { type: String, default: "Seleccione un dato" },
        id: String,
        name: String,
        valueField: { type: String, default: "id" },
        labelField: { type: String, default: "name" },
        fetchOptions: Function,
        required: { type: Boolean, default: false },
        autovalidar: { type: Boolean, default: true },
        disabled: { type: Boolean, default: false }
    },
    data() {
        return {
            search: "",
            showDropdown: false,
            showError: false,
            internalOptions: [],
            isFetching: false,
            isMouseOverDropdown: false // nuevo flag
        };
    },
    computed: {
        filteredOptions() {
            if (this.fetchOptions) return this.internalOptions;
            if (!this.search) return this.internalOptions;
            return this.internalOptions.filter(opt =>
                String(opt[this.labelField]).toLowerCase().includes(this.search.toLowerCase())
            );
        },
        hasOneOption() {
            return this.filteredOptions.length === 1;
        }
    },
    watch: {
        options: {
            handler() {
                this.internalOptions = [...this.options];
                this.autoSelectIfOne();
            },
            immediate: true,
            deep: true
        },
        value(val) {
            this.showDropdown = false;
        }
    },
    mounted() {
        this.autoSelectIfOne();
        this.$emit("register-validation", this);
        if (this.value === undefined) {
            this.$emit("input", null);
        }
    },
    methods: {
        async onSearchInput() {
            if (this.fetchOptions && this.search.length >= 3) {
                this.isFetching = true;
                const results = await this.fetchOptions(this.search);
                this.internalOptions = Array.isArray(results) ? results : [];
                this.isFetching = false;
                this.autoSelectIfOne();
            }
        },
        selectOption(opt) {
            this.$emit("input", String(opt[this.valueField]));
            this.showDropdown = false;
            this.showError = false;
        },
        autoSelectIfOne() {
            if (this.internalOptions.length === 1) {
                const val = String(this.internalOptions[0][this.valueField]);
                if (this.value !== val) {
                    this.$emit("input", val);
                }
            }
        },
        onInvalid() {
            this.showError = true;
        },
        onInput() {
            this.showError = false;
        },
        getLabel(val) {
            const found = this.internalOptions.find(opt => String(opt[this.valueField]) === String(val));
            return found ? found[this.labelField] : "";
        },
        handleBlur() {
            // Solo cierra si el mouse NO está sobre el dropdown
            setTimeout(() => {
                if (!this.isMouseOverDropdown) {
                    this.showDropdown = false;
                }
            }, 150);
        },
        openDropdown() {
            if (!this.disabled) {
                this.showDropdown = true;
                this.$nextTick(() => {
                    if (this.$refs.search) this.$refs.search.focus();
                });
            }
        },
        onDropdownMouseEnter() {
            this.isMouseOverDropdown = true;
        },
        onDropdownMouseLeave() {
            this.isMouseOverDropdown = false;
        }
    },
    template: `
        <div style="position:relative;">
            <div class="custom-select4" :class="{ 'is-invalid': showError }">
            <div
                class="select4-input"
                :tabindex="disabled ? -1 : 0"
                @@click="openDropdown"
                @@focus="openDropdown"
                @@blur="handleBlur"
                :style="{ background: disabled ? '#eee' : '#fff', minHeight: '38px', display: 'flex', alignItems: 'center', border: '1px solid #ced4da', borderRadius: '4px', cursor: disabled ? 'not-allowed' : 'pointer', padding: '0 8px' }"
            >
                <span v-if="getLabel(value)" style="flex:1;">{{ getLabel(value) }}</span>
                <span v-else style="color:#6c757d;flex:1;">{{ placeholder }}</span>
                <span style="margin-left:8px;color:#888;">▼</span>
            </div>
            <input
                v-if="showDropdown"
                ref="search"
                type="text"
                v-model="search"
                @@input="onSearchInput"
                :placeholder="placeholder"
                :disabled="disabled"
                style="position:absolute;top:38px;left:0;width:100%;box-sizing:border-box;padding:6px 8px;border:1px solid #ced4da;border-top:none;border-radius:0 0 4px 4px;outline:none;z-index:11;"
            />
            <ul
                v-show="showDropdown"
                @@mouseenter="onDropdownMouseEnter"
                @@mouseleave="onDropdownMouseLeave"
                style="position:absolute;top:38px;left:0;width:100%;background:#fff;list-style:none;margin:0;padding:0;max-height:420px;overflow-y:auto;box-shadow:0 2px 8px rgba(0,0,0,0.15);border:1px solid #ced4da;border-top:none;border-radius:0 0 4px 4px;z-index:10;"
            >
                <li v-if="isFetching" style="padding:8px;color:#888;">Buscando...</li>
                <li
                v-for="opt in filteredOptions"
                :key="opt[valueField]"
                @@mousedown.prevent="selectOption(opt)"
                style="padding:8px;cursor:pointer;"
                :style="{ background: value == opt[valueField] ? '#e9ecef' : '#fff', color: disabled ? '#aaa' : '#212529' }"
                >
                {{ opt[labelField] }}
                </li>
                <li v-if="!isFetching && filteredOptions.length === 0" style="padding:8px;color:#888;">
                Sin resultados
                </li>
            </ul>
            </div>
            <input
            v-if="required"
            :id="id"
            :name="name"
            :value="value || ''"
            :required="required"
            type="hidden"
            @@invalid="onInvalid"
            @@input="onInput"
            >
            <div v-if="showError" class="invalid-feedback" style="display:block;color:#dc3545;margin-top:4px;">
            Este campo es obligatorio.
            </div>
        </div>
        `
});

Vue.component("select5", {
    props: {
        value: [String, Number, null],
        options: { type: Array, required: true },
        placeholder: { type: String, default: "Seleccione un dato" },
        id: String,
        name: String,
        valueField: { type: String, default: "id" },
        labelField: { type: String, default: "name" },
        fetchOptions: Function,
        required: { type: Boolean, default: false },
        autovalidar: { type: Boolean, default: true },
        disabled: { type: Boolean, default: false }
    },
    data() {
        return {
            internalOptions: [],
            searchTimeout: null,
            showError: false,
            internalValue: null
        };
    },
    watch: {
        options: {
            handler() {
                this.internalOptions = this.formatOptions(this.options);
            },
            immediate: true,
            deep: true
        },
        value: {
            handler(val) {
                this.internalValue = val;
            },
            immediate: true
        }
    },
    mounted() {
        this.internalOptions = this.formatOptions(this.options);
        if (this.value === undefined) {
            this.$emit("input", null);
        }
        this.internalValue = this.value;
        this.$emit("register-validation", this);
    },
    methods: {
        formatOptions(opts) {
            const baseOption = {
                [this.valueField]: "",
                [this.labelField]: this.placeholder,
                __isPlaceholder: true,
                disabled: true
            };
            return [baseOption, ...opts];
        },
        async onSearch(search, loading) {
            if (!this.fetchOptions || !search || search.length < 3) return;
            loading(true);
            if (this.searchTimeout) clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(async () => {
                const results = await this.fetchOptions(search);
                this.internalOptions = this.formatOptions(results);
                loading(false);
            }, 300);
        },
        validate() {
            if (this.required && (this.internalValue === null || this.internalValue === "")) {
                this.showError = true;
                return false;
            }
            this.showError = false;
            return true;
        },
        onInput(selected) {
            let val = "";
            if (selected && typeof selected === "object" && this.valueField in selected) {
                val = selected[this.valueField];
            } else if (typeof selected === "string" || typeof selected === "number") {
                val = selected;
            }
            this.showError = false;
            this.$emit("input", val !== "" ? val : null);
        },
        onBlur() {
            if (this.required && this.autovalidar && (this.value === null || this.value === "")) {
                this.showError = true;
            }
        }
    },
    template: `
        <div class="select5-wrapper">
            <v-select
                :id="id"
                :name="name"
                :options="internalOptions"
                :placeholder="placeholder"
                :reduce="option => option[valueField]"
                :label="labelField"
                v-model="internalValue"
                :disabled="disabled"
                :clearable="!required"
                :searchable="true"
                :filterable="!fetchOptions"
                v-on:input="onInput"
                v-on:search="onSearch"
                v-on:blur="onBlur"
                :class="{'is-invalid': showError, 'select5-select': true}"
            ></v-select>
            <div v-if="showError" class="invalid-feedback select5-error">
                Este campo es obligatorio.
            </div>
        </div>`
});

Vue.component("data-table", {
    props: {
        columns: { type: Array, required: true },
        data: { type: Array, required: true },
        rowsPerPageOptions: { type: Array, default: () => [10, 25, 50, 100] },
        exportName: { type: String, default: "tabla" }
    },
    data() {
        return {
            search: "",
            rowsPerPage: 10,
            currentPage: 1,
            sorts: [] // ✅ ahora puede haber múltiples columnas
        };
    },
    computed: {
        filteredData() {
            let d = this.data;
            if (this.search) {
                d = d.filter(row =>
                    Object.values(row).some(val =>
                        String(val).toLowerCase().includes(this.search.toLowerCase())
                    )
                );
            }
            return d;
        },
        sortedData() {
            if (this.sorts.length === 0) return this.filteredData;

            return [...this.filteredData].sort((a, b) => {
                for (const sort of this.sorts) {
                    const valA = a[sort.key];
                    const valB = b[sort.key];
                    if (valA < valB) return sort.asc ? -1 : 1;
                    if (valA > valB) return sort.asc ? 1 : -1;
                }
                return 0;
            });
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.rowsPerPage;
            return this.sortedData.slice(start, start + this.rowsPerPage);
        },
        totalPages() {
            return Math.ceil(this.sortedData.length / this.rowsPerPage);
        }
    },
    methods: {
        toggleSort(key) {
            const existing = this.sorts.find(s => s.key === key);
            if (existing) {
                existing.asc = !existing.asc; // invierte dirección
            } else {
                this.sorts.push({ key, asc: true }); // agrega nueva columna
            }
        },
        getSortIcon(key) {
            const s = this.sorts.find(sort => sort.key === key);
            if (!s) return "";
            return s.asc ? "↑" : "↓"; // puedes cambiar por <i class="fas fa-sort-up"></i>
        },
        getFileName(extension) {
            const now = new Date();
            const fecha = now.toISOString().replace(/[-:T]/g, "").slice(0, 15);
            return `${this.exportName}_${fecha}.${extension}`;
        },
        exportExcel() {
            const ws = XLSX.utils.json_to_sheet(this.sortedData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Datos");
            XLSX.writeFile(wb, this.getFileName("xlsx"));
        },
        exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.autoTable({
                head: [this.columns.map(c => c.label)],
                body: this.sortedData.map(row => this.columns.map(c => row[c.key]))
            });
            doc.save(this.getFileName("pdf"));
        }
    },
    template: `
        <div id="tableExample3">
            <!-- Header -->
            <div class="d-flex justify-content-between mb-3">
            <div>
                <label>Mostrar: </label>
                <select v-model="rowsPerPage" class="form-select form-select-sm d-inline-block w-auto">
                <option v-for="opt in rowsPerPageOptions" :value="opt" :key="opt">{{opt}}</option>
                </select>
            </div>
            <div>
                <input type="search" v-model="search" class="form-control form-control-sm" placeholder="Buscar...">
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success me-1" v-on:click="exportExcel">
                <i class="fas fa-file-excel"></i> Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" v-on:click="exportPDF">
                <i class="fas fa-file-pdf"></i> PDF
                </button>
            </div>
            </div>
    
            <!-- Tabla -->
            <div class="table-responsive mx-n1 px-1 scrollbar">
            <table class="table fs--1 mb-0 border-top border-200">
                <thead class="text-900">
                <tr>
                    <th v-for="col in columns" :key="col.key"
                        class="sort pe-1 align-middle white-space-nowrap sortable"
                        v-on:click="toggleSort(col.key)"
                        style="cursor:pointer">
                    {{ col.label }}
                    <span>{{ getSortIcon(col.key) }}</span>
                    </th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(row,i) in paginatedData" :key="i" class="hover-actions-trigger">
                    <td v-for="col in columns" :key="col.key" class="align-middle">
                    {{ row[col.key] }}
                    </td>
                </tr>
                <tr v-if="paginatedData.length === 0">
                    <td :colspan="columns.length" class="text-center">No se encontraron resultados</td>
                </tr>
                </tbody>
            </table>
            </div>
    
            <!-- Paginación -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-outline-primary me-2"
                        :disabled="currentPage===1"
                        v-on:click="currentPage--">Anterior</button>
                <span>Página {{currentPage}} de {{totalPages}}</span>
                <button type="button" class="btn btn-sm btn-outline-primary ms-2"
                        :disabled="currentPage===totalPages"
                        v-on:click="currentPage++">Siguiente</button>
            </div>
            </div>
        </div>
        `
});

Vue.component("action-table", {
    props: {
        columns: { type: Array, required: true },
        data: { type: Array, required: true }
    },
    methods: {
        formatValue(key, value) {
            if (key.toLowerCase() === "activo") {
                if (value === 1 || value === true || value === "Activo") {
                    return `<span class="fw-bold text-success">Success</span>
                  <span class="ms-1 fas fa-check text-success"></span>`;
                }
                return `<span class="fw-bold text-danger">Blocked</span>
                <span class="ms-1 fas fa-ban text-danger"></span>`;
            }
            return value ?? "";
        },
        editRow(row) { this.$emit("edit", row); },
        deleteRow(row) { this.$emit("delete", row); },
        toggleRow(row) { this.$emit("toggle", row); }
    },
    template: `
    <div class="table-responsive">
      <table class="table table-striped table-sm fs--1 mb-0">
        <thead>
          <tr>
            <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
            <th class="text-end">Acción</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row,i) in data" :key="i">
            <td v-for="col in columns" :key="col.key" v-html="formatValue(col.key, row[col.key])"></td>
            <td class="align-middle white-space-nowrap text-end pe-0">
              <div class="font-sans-serif btn-reveal-trigger position-static">
                <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2"
                        type="button" data-bs-toggle="dropdown">
                  <i class="fas fa-ellipsis-h"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end py-2">
                  <a class="dropdown-item" v-on:click="editRow(row)">Editar</a>
                  <a class="dropdown-item" v-on:click="toggleRow(row)">
                    {{ row.Activo === 1 || row.Activo === true || row.Activo === 'Activo' ? 'Inactivar' : 'Activar' }}
                  </a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item text-danger" v-on:click="deleteRow(row)">Eliminar</a>
                </div>
              </div>
            </td>
          </tr>
          <tr v-if="data.length === 0">
            <td :colspan="columns.length+1" class="text-center">No hay registros</td>
          </tr>
        </tbody>
      </table>
    </div>
  `
});

Vue.component("condo-action-table", {
    props: {
        columns: { type: Array, required: true },
        data: { type: Array, required: true },
        statusKey: { type: String, default: 'estado' }, 
        photoKey: { type: String, default: 'linkFoto' },
        exportName: { type: String, default: "catalogo_condominios" }
    },
    data() {
        return {
            searchTerm: '',
            rowsPerPage: 10,
            currentPage: 1,
            sorts: []
        };
    },
    computed: {
        filteredData() {
            let d = this.data;
            if (this.searchTerm) {
                const term = this.searchTerm.toLowerCase();
                d = d.filter(row => 
                    Object.values(row).some(val => 
                        String(val).toLowerCase().includes(term)
                    )
                );
            }
            return d;
        },
        sortedData() {
            if (this.sorts.length === 0) return this.filteredData;
            return [...this.filteredData].sort((a, b) => {
                for (const sort of this.sorts) {
                    const valA = a[sort.key];
                    const valB = b[sort.key];
                    if (valA < valB) return sort.asc ? -1 : 1;
                    if (valA > valB) return sort.asc ? 1 : -1;
                }
                return 0;
            });
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.rowsPerPage;
            return this.sortedData.slice(start, start + this.rowsPerPage);
        },
        totalPages() {
            return Math.ceil(this.sortedData.length / this.rowsPerPage);
        },
        columnsForExport() {
             return this.columns.filter(col => col.key !== this.photoKey);
        }
    },
    methods: {
        formatValue(key, value) {
            if (key === this.statusKey) {
                const statusString = String(value).toLowerCase(); 
                const isActive = (statusString === '1' || statusString === 'true' || statusString === 'activo');
                
                if (isActive) {
                    return `<span class="badge bg-success py-1">ACTIVO</span>`;
                }
                return `<span class="badge bg-danger py-1">INACTIVO</span>`;
            }
            
            if (key === this.photoKey && value) {
                return `<button class="btn btn-sm btn-info py-1" onclick="this.closest('[id]').__vue__.showPhotoInternally('${value.replace(/'/g, "\\'")}')">
                            <i class="fas fa-eye me-1"></i> Ver Foto
                        </button>`;
            }
            
            return value ?? "";
        },
        
        showPhotoInternally(link) {
            const fileExtension = link.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension);
            
            if (isImage) {
                Swal.fire({
                    title: 'Imagen',
                    imageUrl: link,
                    imageAlt: 'Elemento adjunto',
                    imageWidth: 400,
                    imageHeight: 300,
                    confirmButtonText: 'Cerrar'
                });
            } else {
                Swal.fire({
                    title: 'Documento',
                    html: `El archivo es un documento (${fileExtension}). <br><a href="${link}" target="_blank" class="btn btn-sm btn-primary mt-2">Descargar/Abrir Documento</a>`,
                    icon: 'info'
                });
            }
        },

        toggleSort(key) {
            const existing = this.sorts.find(s => s.key === key);
            if (existing) {
                existing.asc = !existing.asc; 
            } else {
                this.sorts = [{ key, asc: true }]; 
            }
            this.currentPage = 1;
        },
        getSortIcon(key) {
            const s = this.sorts.find(sort => sort.key === key);
            if (!s) return "";
            return s.asc ? "↑" : "↓"; 
        },
        getFileName(extension) {
            const now = new Date();
            const fecha = now.toISOString().replace(/[-:T]/g, "").slice(0, 15);
            return `${this.exportName}_${fecha}.${extension}`;
        },
        exportExcel() {
            const dataToExport = this.sortedData.map(row => {
                const newRow = {};
                this.columnsForExport.forEach(col => {
                    newRow[col.label] = row[col.key];
                });
                return newRow;
            });
            const ws = XLSX.utils.json_to_sheet(dataToExport);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Datos");
            XLSX.writeFile(wb, this.getFileName("xlsx"));
        },
        exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const head = [this.columnsForExport.map(c => c.label)];
            const body = this.sortedData.map(row => this.columnsForExport.map(c => String(row[c.key] ?? '')));
            doc.autoTable({ head: head, body: body, startY: 20 });
            doc.save(this.getFileName("pdf"));
        },
        editRow(row) { this.$emit("edit", row); },
        deleteRow(row) { this.$emit("delete", row); },
        toggleRow(row) { this.$emit("toggle", row); },
        changePage(page) {
             if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        }
    },
    template: `
    <div id="condoTableContainer">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center mb-2 mb-md-0 order-md-1">
                <label class="me-2">Mostrar:</label>
                <select v-model="rowsPerPage" class="form-select form-select-sm d-inline-block w-auto">
                    <option v-for="opt in [10, 25, 50, 100]" :value="opt" :key="opt">{{opt}}</option>
                </select>
            </div>
            
            <div class="input-group input-group-sm order-md-2" style="max-width: 300px;">
                <span class="input-group-text fas fa-search text-600"></span>
                <input type="text" class="form-control" v-model="searchTerm" placeholder="Buscar en la tabla..." />
            </div>
            
            <div class="d-flex mt-2 mt-md-0 order-md-3">
                <button type="button" class="btn btn-sm btn-outline-success me-2" @click="exportExcel">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="exportPDF">
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-sm fs--1 mb-0 border-top border-200">
                <thead class="text-900">
                    <tr>
                        <th v-for="col in columns" :key="col.key"
                            class="sort pe-1 align-middle white-space-nowrap sortable"
                            @click="toggleSort(col.key)"
                            style="cursor:pointer">
                            {{ col.label }}
                            <span>{{ getSortIcon(col.key) }}</span>
                        </th>
                        <th class="text-end align-middle white-space-nowrap">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row,i) in paginatedData" :key="i">
                        <td v-for="col in columns" :key="col.key" v-html="formatValue(col.key, row[col.key])"></td>
                        <td class="align-middle white-space-nowrap text-end pe-0">
                            <div class="font-sans-serif btn-reveal-trigger position-static">
                                <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2"
                                        type="button" data-bs-toggle="dropdown" data-boundary="window">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end py-2">
                                    <a class="dropdown-item" @click="editRow(row)">Editar</a>
                                    <a class="dropdown-item" @click="toggleRow(row)">
                                        {{ row[statusKey] == 1 || row[statusKey] == true || String(row[statusKey]).toLowerCase() == 'activo' ? 'Inactivar' : 'Activar' }}
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" @click="deleteRow(row)">Eliminar</a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="paginatedData.length === 0">
                        <td :colspan="columns.length+1" class="text-center py-3">No se encontraron resultados</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
            <span>Mostrando {{ (currentPage - 1) * rowsPerPage + 1 }} a {{ Math.min(currentPage * rowsPerPage, sortedData.length) }} de {{ sortedData.length }} registros</span>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-outline-primary me-2"
                        :disabled="currentPage===1"
                        @click="changePage(currentPage - 1)">Anterior</button>
                <span>Página {{currentPage}} de {{totalPages}}</span>
                <button type="button" class="btn btn-sm btn-outline-primary ms-2"
                        :disabled="currentPage===totalPages || totalPages === 0"
                        @click="changePage(currentPage + 1)">Siguiente</button>
            </div>
        </div>
    </div>
    `
});

Vue.component("alertas-globales", {
    // Mantener el mixin para acceder a los arrays de alertas y métodos de cierre
    //mixins: [alertasMixin],

    // Alertas locales para manejar los temporizadores (Soft y Outline)
    data() {
        return {
            timerRefsSoft: [],
            timerRefsOutline: []
        };
    },

    methods: {
        iconClass(type) {
            switch (type) {
                case "success": return "fas fa-check-circle text-success fs-3 me-3";
                case "danger": return "fas fa-times-circle text-danger fs-3 me-3";
                case "warning": return "fas fa-info-circle text-warning fs-3 me-3";
                case "info": return "fas fa-info-circle text-info fs-3 me-3";
                default: return "fas fa-bell text-primary fs-3 me-3";
            }
        },

        // -----------------------------------------------------------
        // LÓGICA DE TEMPORIZADOR
        // -----------------------------------------------------------
        setupTimer(index, type) {
            const timerDuration = 5000; // 5 segundos
            const timerArray = type === 'soft' ? this.timerRefsSoft : this.timerRefsOutline;
            const closeMethod = type === 'soft' ? this.cerrarAlertaSoft : this.cerrarAlertaOutline;

            // Si la alerta no es descartable (dismissible: false), no programar el cierre
            if (this[type === 'soft' ? 'alertasSoft' : 'alertasOutline'][index].dismissible === false) {
                return;
            }

            // Limpiar cualquier temporizador previo si la alerta se actualizó
            if (timerArray[index]) {
                clearTimeout(timerArray[index]);
            }

            // Programar el cierre
            const timer = setTimeout(() => {
                closeMethod(index);
            }, timerDuration);

            // Almacenar la referencia del temporizador
            timerArray[index] = timer;
        },

        // Limpiar temporizadores cuando el componente se destruye o la lista cambia
        clearAllTimers() {
            this.timerRefsSoft.forEach(clearTimeout);
            this.timerRefsOutline.forEach(clearTimeout);
            this.timerRefsSoft = [];
            this.timerRefsOutline = [];
        }
    },

    watch: {
        // Observar si las listas de alertas cambian para reiniciar temporizadores
        alertasSoft: {
            handler() {
                this.$nextTick(() => {
                    this.alertasSoft.forEach((alert, i) => this.setupTimer(i, 'soft'));
                });
            },
            deep: true
        },
        alertasOutline: {
            handler() {
                this.$nextTick(() => {
                    this.alertasOutline.forEach((alert, i) => this.setupTimer(i, 'outline'));
                });
            },
            deep: true
        }
    },

    beforeDestroy() {
        // Asegurar que no quedan temporizadores activos al destruir el componente
        this.clearAllTimers();
    },

    template: `
    <div class="alertas-container">
      <div v-for="(alert, i) in alertasSoft"
           :key="'soft-' + i"
           :class="'alert alert-soft-' + alert.type + ' d-flex align-items-center flex-column shadow-lg'"
           role="alert">
        
        <div class="d-flex align-items-center w-100">
            <span :class="iconClass(alert.type)"></span>
            <p class="mb-0 flex-grow-1" v-html="alert.message"></p>
            <button v-if="alert.dismissible !== false"
                    type="button"
                    class="btn-close ms-2"
                    v-on:click="cerrarAlertaSoft(i)">
            </button>
        </div>

        <div v-if="alert.dismissible !== false" 
             class="progress-bar-line" 
             :class="'bg-' + alert.type" 
             style="animation-duration: 5s;">
        </div>
      </div>

      <div v-for="(alert, i) in alertasOutline"
           :key="'outline-' + i"
           :class="'alert alert-outline-' + alert.type + ' d-flex align-items-center flex-column shadow-lg'"
           role="alert">
        
        <div class="d-flex align-items-center w-100">
            <span :class="iconClass(alert.type)"></span>
            <p class="mb-0 flex-grow-1" v-html="alert.message"></p>
            <button v-if="alert.dismissible !== false"
                    type="button"
                    class="btn-close ms-2"
                    v-on:click="cerrarAlertaOutline(i)">
            </button>
        </div>

        <div v-if="alert.dismissible !== false"
             class="progress-bar-line" 
             :class="'bg-' + alert.type" 
             style="animation-duration: 5s;">
        </div>
      </div>
    </div>
  `
});

Vue.component("upload-file-s3", {
    model: {
        prop: "value",
        event: "input"
    },

    props: {
        value: { type: String, default: "" },
        name: { type: String, default: "" },
        id: { type: String, default: "" },
        carpeta: { type: String, default: "ImagenesNotificaciones" },
        multiple: { type: Boolean, default: true },
        formatos: { type: String, default: ".jpg,.png,.jpeg,.gif,.pdf" },
        max: { type: Number, default: 5 }
    },

    data() {
        return {
            archivos: [],
            previews: [],
            cargando: false // 1. Nuevo estado de carga
        };
    },

    computed: {
        puedeSubir() {
            // Solo se puede subir si hay archivos y NO se está cargando ya
            return this.archivos.length > 0 && !this.cargando;
        }
    },

    watch: {
        value(newVal) {
            if (newVal && this.previews.length === 0) {
                this.previews.push({
                    type: newVal.endsWith(".pdf") ? "pdf" : "img",
                    data: newVal
                });
            }
            if (!newVal) {
                this.archivos = [];
                this.previews = [];
            }
        }
    },

    mounted() {
        if (this.value) {
            this.previews.push({
                type: this.value.endsWith(".pdf") ? "pdf" : "img",
                data: this.value
            });
        }
    },

    methods: {
        onFileChange(e) {
            if (this.cargando) return; // Bloquear si está subiendo

            const files = [...e.target.files];
            this.archivos = [];
            this.previews = [];

            files.forEach(f => {
                if (f.size > this.max * 1024 * 1024) {
                    Swal.fire("Archivo muy grande", `El archivo ${f.name} supera los ${this.max}MB`, "warning");
                    return;
                }

                this.archivos.push(f);

                if (f.type.includes("image")) {
                    const reader = new FileReader();
                    reader.onload = evt => this.previews.push({ type: "img", data: evt.target.result });
                    reader.readAsDataURL(f);
                } else {
                    this.previews.push({ type: "pdf", data: "PDF" });
                }
            });
        },

        eliminarLocal(i) {
            if (this.cargando) return;
            this.archivos.splice(i, 1);
            this.previews.splice(i, 1);
            if (!this.previews.length) {
                this.$emit("input", "");
            }
        },

        async subir() {
            if (!this.archivos.length) return;

            this.cargando = true; // Activar loading

            const formData = new FormData();
            this.archivos.forEach(f => formData.append("file[]", f));
            formData.append("carpeta", JSON.stringify({ carpeta: this.carpeta }));

            try {
                const response = await fetch(server + basePath + "/services/condominios/S3/s3", {
                    method: "POST",
                    body: formData
                });

                if (!response.ok) throw new Error("Error en el servidor");

                const data = await response.json();
                const url = data[0];

                this.$emit("input", url); 
                this.$emit("uploaded", data);

                // 2. Mensaje de éxito profesional
                Swal.fire({
                    icon: 'success',
                    title: '¡Subida Exitosa!',
                    text: 'El archivo se ha guardado correctamente.',
                    timer: 2000,
                    showConfirmButton: false
                });

                this.archivos = []; // Limpiar selección local tras éxito

            } catch (err) {
                console.error(err);
                // 2. Mensaje de error profesional
                Swal.fire("Error de subida", "No se pudo subir el archivo. Intente de nuevo.", "error");
                this.$emit("error", err);
            } finally {
                this.cargando = false; // Desactivar loading
            }
        }
    },

    template: `
        <div class="card shadow-none border border-300" :id="id">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 text-800"><i class="fas fa-paperclip me-2"></i> Adjuntar Documento/Imagen</h6>
            </div>

            <div class="card-body">
                <div class="preview-container text-center border border-2 border-dashed p-3 rounded-3 position-relative" 
                     :class="{'bg-light-subtle': cargando}">
                    
                    <div v-if="cargando" class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-white bg-opacity-75 rounded-3" style="z-index: 10;">
                        <div class="spinner-border text-primary mb-2" role="status"></div>
                        <span class="fw-bold text-primary">Subiendo archivo...</span>
                    </div>

                    <label class="custom-file-upload d-block" :class="cargando ? 'cursor-not-allowed' : 'cursor-pointer'">
                        <i class="fas fa-cloud-upload-alt text-info fs-2 mb-2"></i>
                        <span class="d-block fw-bold text-700">Haga click para seleccionar</span>
                        <span class="d-block text-500 fs--2">Formatos: {{formatos}} (Máx. {{max}}MB)</span>

                        <input
                            :disabled="cargando"
                            :name="name"
                            :id="id"
                            type="file"
                            :accept="formatos"
                            :multiple="multiple"
                            style="display:none"
                            @change="onFileChange"
                        >
                    </label>

                    <div class="mt-3 row g-2 justify-content-center">
                        <div v-for="(p,i) in previews" :key="i" class="col-auto text-center position-relative">
                            <div class="border rounded p-1 bg-white shadow-sm">
                                <img v-if="p.type === 'img'" :src="p.data" class="rounded" style="height:80px; width:80px; object-fit: cover;">
                                <div v-else class="d-flex align-items-center justify-content-center bg-soft-danger rounded" style="height:80px; width:80px;">
                                    <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                </div>
                                <button v-if="!cargando" class="btn btn-sm btn-circle btn-danger position-absolute top-0 end-0 mt-n2 me-n2 shadow"
                                        @click="eliminarLocal(i)">
                                    <i class="fas fa-times fs--2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-sm btn-primary px-4 w-100"
                                :disabled="!puedeSubir"
                                @click="subir">
                            <i v-if="!cargando" class="fas fa-upload me-2"></i>
                            <span v-else class="spinner-border spinner-border-sm me-2"></span>
                            {{ cargando ? 'Subiendo...' : 'Iniciar Subida' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
});

Vue.component("v-smart-table", {
    props: {
        title: { type: String, default: "" },
        data: { type: Array, required: true },
        columns: { type: Array, required: true },
        exportName: { type: String, default: "Reporte" },
        photoKey: { type: String, default: "" },
        statusKey: { type: String, default: "" },
        rowsPerPageOptions: { type: Array, default: () => [5, 10, 15, 25, 50, 100] }
    },
    data() {
        return {
            searchQuery: '',
            currentPage: 1,
            rowsPerPage: 10,
            sortKey: '',
            sortOrder: 1
        };
    },
    watch: {
        searchQuery() { this.currentPage = 1; },
        rowsPerPage() { this.currentPage = 1; }
    },
    computed: {
        // 🔥 ACTUALIZADO: Ahora detecta también el evento 'salida'
        hasActions() {
            return !!(this.$listeners.edit || this.$listeners.delete || this.$listeners.view || this.$listeners.salida);
        },
        filteredData() {
            let result = this.data;
            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(item => 
                    Object.values(item).some(val => String(val).toLowerCase().includes(query))
                );
            }
            if (this.sortKey) {
                result = [...result].sort((a, b) => {
                    const valA = a[this.sortKey];
                    const valB = b[this.sortKey];
                    return (valA > valB ? 1 : -1) * this.sortOrder;
                });
            }
            return result;
        },
        paginatedData() {
            const start = (this.currentPage - 1) * this.rowsPerPage;
            return this.filteredData.slice(start, start + this.rowsPerPage);
        },
        totalPages() {
            return Math.ceil(this.filteredData.length / this.rowsPerPage) || 1;
        }
    },
    methods: {
        sortBy(key) {
            if (this.sortKey === key) this.sortOrder *= -1;
            else { this.sortKey = key; this.sortOrder = 1; }
        },
        getStatusClass(status) {
            const s = String(status).toLowerCase();
            if (['activo', 'completado', 'entrada', 'vehiculo', 'verificado','autorizado'].includes(s)) return 'badge-phoenix-success';
            if (['inactivo', 'cancelado', 'salida', 'error'].includes(s)) return 'badge-phoenix-danger';
            if (['denegado'].includes(s)) return 'badge-phoenix-secondary';
            if (['pendiente'].includes(s)) return 'badge-phoenix-warning';
            return 'badge-phoenix-warning';
        },
        exportExcel() {
            const dataToExport = this.filteredData.map(item => {
                let row = {};
                this.columns.forEach(col => {
                    if (col.key !== this.photoKey) {
                        row[col.label] = item[col.key] || '---';
                    }
                });
                return row;
            });
            const ws = XLSX.utils.json_to_sheet(dataToExport);
            const colWidths = this.columns.map(col => ({ wch: col.label.length + 15 }));
            ws['!cols'] = colWidths;
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, "Registros");
            const fileName = `${this.exportName}_${moment().format('DDMMYYYY_HHmm')}.xlsx`;
            XLSX.writeFile(wb, fileName);
        },
        exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            const totalPagesExp = "{total_pages_count_string}";
            const tableColumn = this.columns.map(c => c.label);
            const tableRows = this.filteredData.map(item => this.columns.map(c => item[c.key] || '---'));
            doc.autoTable({
                head: [tableColumn],
                body: tableRows,
                startY: 30,
                theme: 'striped',
                headStyles: { fillColor: [52, 73, 94], textColor: [255, 255, 255], fontSize: 10, halign: 'center' },
                bodyStyles: { fontSize: 9 },
                alternateRowStyles: { fillColor: [245, 247, 250] },
                margin: { top: 30 },
                didDrawPage: (data) => {
                    doc.setFontSize(18);
                    doc.setTextColor(40);
                    doc.text(this.title.toUpperCase(), data.settings.margin.left, 15);
                    doc.setFontSize(10);
                    doc.setTextColor(100);
                    doc.text(`Reporte generado el: ${moment().format('DD/MM/YYYY HH:mm:ss')}`, data.settings.margin.left, 22);
                    doc.setDrawColor(52, 73, 94);
                    doc.setLineWidth(0.5);
                    doc.line(data.settings.margin.left, 25, 280, 25);
                    let str = "Página " + doc.internal.getNumberOfPages();
                    if (typeof doc.putTotalPages === 'function') str = str + " de " + totalPagesExp;
                    doc.setFontSize(9);
                    doc.text(str, data.settings.margin.left, doc.internal.pageSize.height - 10);
                }
            });
            if (typeof doc.putTotalPages === 'function') doc.putTotalPages(totalPagesExp);
            doc.save(`${this.exportName}_${moment().format('DDMMYYYY')}.pdf`);
        }
    },
    template: `
    <div class="card shadow-none border border-300 my-3">
        <div class="card-header border-bottom border-200 py-2">
            <div class="row align-items-center justify-content-between">
                <div class="col-auto"><h5 class="mb-0 text-900">{{ title }}</h5></div>
                <div class="col-auto d-flex gap-2">
                    <button @click="exportExcel" class="btn btn-link text-900 btn-sm px-2" title="Exportar Excel"><i class="fas fa-file-excel text-success fs-1"></i></button>
                    <button @click="exportPDF" class="btn btn-link text-900 btn-sm px-2" title="Exportar PDF"><i class="fas fa-file-pdf text-danger fs-1"></i></button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="p-3 bg-light border-bottom border-200">
                <div class="row g-2 justify-content-between">
                    <div class="col-12 col-md-4">
                        <div class="search-box w-100">
                            <input v-model="searchQuery" class="form-control form-control-sm" type="search" placeholder="Buscar..." />
                            <span class="fas fa-search search-box-icon"></span>
                        </div>
                    </div>
                    <div class="col-auto d-flex align-items-center">
                        <small class="me-2 fw-bold text-700">Ver:</small>
                        <select v-model="rowsPerPage" class="form-select form-select-sm w-auto">
                            <option v-for="o in rowsPerPageOptions" :value="o">{{o}}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive scrollbar">
                <table class="table table-sm fs--1 mb-0 text-900">
                    <thead>
                        <tr class="bg-200">
                            <th v-for="col in columns" :key="col.key" @click="sortBy(col.key)" class="white-space-nowrap align-middle ps-3 cursor-pointer py-2">
                                {{ col.label }} 
                                <i v-if="sortKey === col.key" :class="sortOrder === 1 ? 'fa-sort-up' : 'fa-sort-down'" class="fas ms-1 text-primary"></i>
                                <i v-else class="fas fa-sort ms-1 text-400 fs--2"></i>
                            </th>
                            <th v-if="hasActions" class="text-end align-middle pe-3 py-2">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody class="list">
                        <tr v-for="(item, i) in paginatedData" :key="i" class="hover-actions-trigger">
                            <td v-for="col in columns" :key="col.key" class="align-middle ps-3">
                                <div v-if="col.key === photoKey" class="avatar avatar-m">
                                    <img v-if="item[col.key]" :src="item[col.key]" class="rounded-circle border border-200 shadow-sm" />
                                    <div v-else class="avatar-name rounded-circle"><span>?</span></div>
                                </div>
                                <span v-else-if="col.key === statusKey" class="badge badge-phoenix fs--2" :class="getStatusClass(item[col.key])">
                                    {{ item[col.key] }}
                                </span>
                                <span v-else class="fw-semi-bold text-800">{{ item[col.key] || '---' }}</span>
                            </td>

                            <td v-if="hasActions" class="align-middle text-end pe-3">
                                <div class="btn-group">
                                    <button v-if="$listeners.salida" class="btn btn-sm btn-phoenix-danger me-1" @click="$emit('salida', item)" title="Marcar Salida">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </button>

                                    <button v-if="$listeners.view" class="btn btn-sm btn-phoenix-secondary me-1" @click="$emit('view', item)" title="Ver Detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button v-if="$listeners.edit" class="btn btn-sm btn-phoenix-primary me-1" @click="$emit('edit', item)" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button v-if="$listeners.delete" class="btn btn-sm btn-phoenix-danger" @click="$emit('delete', item)" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between p-3 border-top border-200 bg-light-subtle">
                <span class="fs--1 text-700">Mostrando registros del <b>{{ (currentPage-1)*rowsPerPage + 1 }}</b> al <b>{{ Math.min(currentPage*rowsPerPage, filteredData.length) }}</b> de <b>{{filteredData.length}}</b></span>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-outline-secondary" :disabled="currentPage === 1" @click="currentPage--">Anterior</button>
                    <div class="fs--1 fw-bold text-primary">{{currentPage}} / {{totalPages}}</div>
                    <button class="btn btn-sm btn-outline-secondary" :disabled="currentPage === totalPages" @click="currentPage++">Siguiente</button>
                </div>
            </div>
        </div>
    </div>
    `
});

//////////// -----MODALES-------- ////////////
Vue.component("v-modal", {
    props: {
        value: { type: Boolean, default: false },
        id: { type: String, required: true },
        title: { type: String, default: "Detalles del Registro" },
        size: { type: String, default: "modal-lg" },
        fields: { type: Array, default: () => [] },
        data: { type: Object, default: () => ({}) },
        imageField: { type: String, default: '' },
        noFooter: { type: Boolean, default: true }
    },
    data() { return { modalInstance: null }; },
    watch: {
        value(val) {
            this.$nextTick(() => { // Esperamos a que Vue termine de renderizar
                if (!this.modalInstance) this.initModal();
                val ? this.modalInstance.show() : this.modalInstance.hide();
            });
        }
    },
    methods: {
        initModal() {
            // Usamos $refs en lugar de document.getElementById para mayor seguridad
            const el = this.$refs.modalElement; 
            if (el) {
                this.modalInstance = new bootstrap.Modal(el);
                el.addEventListener('hidden.bs.modal', () => { 
                    this.$emit('input', false); 
                });
            }
        },
        verImagenFull(url) {
            if (url) window.open(url, '_blank');
        }
    },
    beforeDestroy() {
        // Limpiamos la instancia de Bootstrap para que no deje basura en el DOM
        if (this.modalInstance) {
            this.modalInstance.dispose();
        }
    },
    template: `
    <div class="modal fade" :id="id" ref="modalElement" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered" :class="size">
        <div class="modal-content shadow-lg border-0">
          
          <div class="modal-header border-0 bg-light-subtle py-3 ps-4 pe-3">
            <h5 class="modal-title fw-bold text-900">
                <span class="fas fa-file-alt me-2 text-primary"></span>{{ title }}
            </h5>
            <button class="btn btn-sm btn-circle btn-light" type="button" data-bs-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="modal-body px-4 pb-4">
            <div class="row g-4">
                
                <div v-if="imageField" key="col-image" class="col-md-5"> 
                    <div class="image-detail-container position-relative">
                        <div v-if="data[imageField]" key="has-img" class="text-center">
                            <img :src="data[imageField]" 
                                 class="rounded shadow-sm img-fluid border w-100 cursor-pointer"
                                 style="object-fit: cover; max-height: 400px; min-height: 250px;"
                                 @click="verImagenFull(data[imageField])">
                        </div>
                        <div v-else key="no-img" class="bg-light rounded py-5 border text-300 text-center">
                            <i class="fas fa-image fa-4x"></i>
                        </div>
                    </div>
                </div>

                <div :class="imageField ? 'col-md-7' : 'col-12'" key="col-content">
                    <div class="row g-3">
                        <div v-for="(f, index) in fields" :key="'f-' + (f.key || index)" :class="f.col || 'col-sm-6'">
                            
                            <h6 v-if="f.isHeader" key="header" class="text-primary border-bottom pb-2 mt-2 mb-3 fw-bold">
                                {{ f.label }}
                            </h6>
                            
                            <div v-else key="field" class="detail-field">
                                <label class="text-500 fs--2 fw-bold text-uppercase d-block mb-1">{{ f.label }}</label>
                                
                                <div v-if="f.type === 'textarea'" key="txt" class="bg-light p-2 rounded border-start border-primary border-3">
                                    <p class="text-800 mb-0 fs--1 italic">{{ data[f.key] || '---' }}</p>
                                </div>
                                <p v-else key="val" :class="f.valueClass || 'text-900 mb-0 fw-semi-bold fs--1'">
                                    {{ data[f.key] || '---' }}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
          </div>
        </div>
      </div>
    </div>
    `
});

Vue.component("v-modal-form", {
    props: {
        value: { type: Boolean, default: false },
        id: { type: String, required: true },
        title: { type: String, default: "Formulario" },
        size: { type: String, default: "modal-md" },
        btnText: { type: String, default: "Guardar Cambios" },
        btnClass: { type: String, default: "btn-primary" },
        loading: { type: Boolean, default: false }
    },
    data() { return { modalInstance: null }; },
    watch: {
        value(val) {
            if (!this.modalInstance) this.initModal();
            val ? this.modalInstance.show() : this.modalInstance.hide();
        }
    },
    methods: {
        initModal() {
            const el = document.getElementById(this.id);
            this.modalInstance = new bootstrap.Modal(el);
            el.addEventListener('hidden.bs.modal', () => { 
                this.$emit('input', false); 
            });
        },
        onAction() {
            this.$emit('action');
        }
    },
    template: `
    <div class="modal fade" :id="id" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered" :class="size">
        <div class="modal-content shadow-lg border-0">
          
          <div class="modal-header border-bottom-0 bg-light-subtle py-3 ps-4 pe-3">
            <h5 class="modal-title fw-bold text-900">
                <i class="fas fa-edit me-2 text-primary"></i>{{ title }}
            </h5>
            <button class="btn btn-sm btn-circle btn-light" type="button" data-bs-dismiss="modal">
                <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="modal-body px-4">
            <slot></slot>
          </div>

          <div class="modal-footer border-top-0 px-4 pb-4">
            <button type="button" class="btn btn-link text-danger fw-bold text-decoration-none" data-bs-dismiss="modal">
                Cancelar
            </button>
            <button type="button" class="btn px-5 shadow-sm" :class="btnClass" :disabled="loading" @click="onAction">
                <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="fas fa-check me-2"></i>
                {{ btnText }}
            </button>
          </div>

        </div>
      </div>
    </div>
    `
});
///// ---------- final ----------------////////
(function(document) {
    'use strict';
    var LightTableFilter = (function(Arr) {
        var _input;
        function _onInputEvent(e) {
            _input = e.target;
            var tables = document.getElementsByClassName(_input.getAttribute('data-table'));
            Arr.forEach.call(tables, function(table) {
                Arr.forEach.call(table.tBodies, function(tbody) {
                    Arr.forEach.call(tbody.rows, _filter);
                });
            });
        }

        function _filter(row) {
            var text = row.textContent.toLowerCase(), val = _input.value.toLowerCase();
            row.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
        }

        return {
            init: function() {
                var inputs = document.getElementsByClassName('form-control col-md-3 light-table-filter');
                Arr.forEach.call(inputs, function(input) {
                    input.oninput = _onInputEvent;
                });
            }
        };
    })(Array.prototype);

    document.addEventListener('readystatechange', function() {
        if (document.readyState === 'complete') {
            LightTableFilter.init();
        }
    });

})(document);