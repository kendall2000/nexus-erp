Vue.component("issues-card", {
    props: {
        title: { type: String, required: true },
        subtitle: { type: String, required: true },
        total: { type: Number, required: true },
        issues: { type: Array, required: true }
    },
    methods: {
        handleDetails() {
            this.$emit("see-details");
        },
        renderChart() {
            if (!this.$refs.issuesChart) return;

            // Inicializa instancia de ECharts
            this.chart = echarts.init(this.$refs.issuesChart);

            const option = {
                tooltip: {
                    trigger: "item"
                },
                legend: {
                    orient: "vertical",
                    left: "left"
                },
                series: [
                    {
                        name: "Issues",
                        type: "pie",
                        radius: "70%",
                        data: this.issues.map(i => ({
                            value: i.count,
                            name: i.name
                        })),
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: "rgba(0, 0, 0, 0.5)"
                            }
                        }
                    }
                ]
            };

            this.chart.setOption(option);
        }
    },
    mounted() {
        this.renderChart();
        window.addEventListener("resize", () => {
            if (this.chart) this.chart.resize();
        });
    },
    watch: {
        issues: {
            deep: true,
            handler() {
                this.renderChart();
            }
        }
    },
    template: `
    <div class="col-12 col-xl-7 col-xxl-6">
      <div class="row g-3 mb-3">
        
        <!-- Columna Izquierda -->
        <div class="col-12 col-md-6">
          <h3 class="text-1100 text-nowrap">{{ title }}</h3>
          <p class="text-700 mb-md-7">{{ subtitle }}</p>

          <div class="d-flex align-items-center justify-content-between">
            <p class="mb-0 fw-bold">Issue type </p>
            <p class="mb-0 fs--1">Total count <span class="fw-bold">{{ total }}</span></p>
          </div>

          <hr class="bg-200 mb-2 mt-2">

          <div v-for="(issue, index) in issues" :key="index" 
               class="d-flex align-items-center mb-1">
            <span class="d-inline-block bullet-item me-2" :class="issue.color"></span>
            <p class="mb-0 fw-semi-bold text-900 lh-sm flex-1">{{ issue.name }}</p>
            <h5 class="mb-0 text-900">{{ issue.count }}</h5>
          </div>

          <!--<button class="btn btn-outline-primary mt-5" @click="handleDetails">
            See Details
            <i class="fas fa-angle-right ms-2 fs&#45;&#45;2 text-center"></i>
          </button>-->
        </div>

        <!-- Columna Derecha (gráfico con ECharts) -->
        <div class="col-12 col-md-6">
          <div class="position-relative mb-sm-4 mb-xl-0">
            <div ref="issuesChart" style="min-height:390px; width:100%;"></div>
          </div>
        </div>

      </div>
    </div>
  `
});
Vue.component("dashboard-card", {
    props: {
        title: { type: String, required: true },
        subtitle: { type: String, required: true },
        items: { type: Array, required: true }
    },
    data() {
        return { chart: null };
    },
    computed: {
        total() {
            return this.items.reduce((sum, it) => sum + (it.count || 0), 0);
        }
    },
    methods: {
        generateColors(n) {
            const colors = [];
            for (let i = 0; i < n; i++) {
                const hue = (i * 360 / n) % 360;
                colors.push(`hsl(${hue}, 70%, 55%)`);
            }
            return colors;
        },
        renderChart() {
            if (!this.$refs.chartBox) return;
            if (!this.chart) {
                this.chart = echarts.init(this.$refs.chartBox);
            }

            const autoColors = this.generateColors(this.items.length);
            const option = {
                tooltip: { trigger: "item" },
                legend: { type: "scroll", orient: "vertical", left: "left", show: false },
                series: [
                    {
                        name: this.title,
                        type: "pie",
                        radius: ["40%", "70%"],
                        avoidLabelOverlap: false,
                        label: { show: false, position: "center" },
                        emphasis: {
                            label: { show: true, fontSize: 16, fontWeight: "bold" }
                        },
                        labelLine: { show: false },
                        data: this.items.map((i, idx) => ({
                            value: i.count,
                            name: i.name,
                            itemStyle: {
                                color: i.color || autoColors[idx % autoColors.length]
                            }
                        }))
                    }
                ]
            };
            this.chart.setOption(option, { notMerge: true, lazyUpdate: true });
        },
        async exportPDF() {
            const { jsPDF } = window.jspdf;
            const element = this.$refs.exportBox;

            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL("image/png");

            const pdf = new jsPDF("landscape", "mm", "a4");
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();

            const imgWidth = pageWidth - 20;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            pdf.addImage(imgData, "PNG", 50, 50, imgWidth, imgHeight);
            pdf.save(`${this.title.replace(/\s+/g, "_")}.pdf`);
        }
    },
    mounted() {
        this.renderChart();
        window.addEventListener("resize", () => this.chart && this.chart.resize());
    },
    watch: {
        items: {
            deep: true,
            handler() {
                this.renderChart();
            }
        }
    },
    template: `
    <div>
      <!-- 🔥 Botón fuera del bloque exportable -->
      <div class="mt-3 d-flex justify-content-end">
        <button type="button" class="btn btn-outline-danger" @click="exportPDF">
          Exportar PDF <i class="fas fa-file-pdf ms-2"></i>
        </button>
      </div>
    
      <!-- 🔥 Bloque exportable -->
      <div ref="exportBox" class="col-12">
        <div class="row g-3 mb-3">
    
          <!-- Texto Izquierda -->
          <div class="col-12 col-md-6">
            <h3 class="text-1100 text-nowrap">{{ title }}</h3>
            <p class="text-700 mb-md-7">{{ subtitle }}</p>
    
            <div class="d-flex align-items-center justify-content-between">
              <p class="mb-0 fw-bold">Categorias</p>
              <p class="mb-0 fs--1">Total <span class="fw-bold">{{ total }}</span></p>
            </div>
    
            <hr class="bg-200 mb-2 mt-2">
    
            <div v-for="(it, idx) in items" :key="idx" class="d-flex align-items-center mb-1">
              <span class="d-inline-block bullet-item me-2"
                    :style="{ backgroundColor: it.color || generateColors(items.length)[idx % items.length] }"></span>
              <p class="mb-0 fw-semi-bold text-900 lh-sm flex-1">{{ it.name }}</p>
              <h5 class="mb-0 text-900">{{ it.count }}</h5>
            </div>
          </div>
    
          <!-- Gráfico Derecha -->
          <div class="col-12 col-md-6">
            <div class="position-relative mb-sm-4 mb-xl-0">
              <div ref="chartBox" style="min-height:390px; width:100%;"></div>
            </div>
          </div>
    
        </div>
      </div>      
    </div>
  `
});