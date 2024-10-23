<template>
  <v-container>
    <v-data-table
      :headers="providerHeaders"
      :items="providerSummaries"
      item-value="id"
      id="provTable"
      show-expand
      single-expand
      class="elevation-1 common-table"
      :expanded.sync="expanded"
      @click:row="toggleExpand"
      fixed-header
      height="80vh"
    >
      <template v-slot:expanded-item="{ item }">
        <td :colspan="providerHeaders.length + 1">
          <v-data-table
            :headers="dateHeaders"
            :items="item.dates"
            item-value="date"
            id="dateTable"
            show-expand
            hide-default-header
            hide-default-footer
            class="common-table date-table"
          >
            <template v-slot:expanded-item="{ item: dateItem }">
              <td :colspan="providerHeaders.length + 1">
                <v-data-table
                  :headers="geoHeaders"
                  :items="dateItem.geo"
                  item-value="geo"
                  id="geoTable"
                  hide-default-header
                  hide-default-footer
                  class="common-table"
                  v-model.lazy.trim="importSelected"
                  show-select
                >
                  <template v-slot:item="{ item: geoItem }">
                    <tr>
                      <td>
                        <v-checkbox
                          v-model="importSelected"
                          :value="geoItem"
                        ></v-checkbox>
                      </td>
                      <td class="text-start common-column" width="150px">
                        {{ geoItem.geo }}
                      </td>
                      <td class="text-start common-column" width="100px">
                        {{ geoItem.sp }}
                      </td>
                      <td class="text-center common-column" width="100px">
                        {{ geoItem.sum }}
                      </td>
                      <td class="text-center common-column new" width="100px">
                        {{ geoItem.hmnew }}
                      </td>
                      <td class="text-center common-column renew" width="100px">
                        {{ geoItem.hmrenew }}
                      </td>
                      <td
                        class="text-center common-column callback"
                        width="100px"
                      >
                        {{ geoItem.hmcb }}
                      </td>
                      <td
                        class="text-center common-column deposit"
                        width="100px"
                      >
                        {{ geoItem.hmdp }}
                      </td>
                      <td
                        class="text-center common-column pending"
                        width="100px"
                      >
                        {{ geoItem.hmpnd }}
                      </td>
                      <td
                        class="text-center common-column potential"
                        width="100px"
                      >
                        {{ geoItem.hmpot }}
                      </td>
                      <td class="text-center common-column noans" width="100px">
                        {{ geoItem.hmnoans }}
                      </td>
                      <td
                        class="text-center common-column nointerest"
                        width="100px"
                      >
                        {{ geoItem.hmnointerest }}
                      </td>
                      <td class="text-center common-column" width="100px">
                        {{ geoItem.hm }}
                      </td>
                    </tr>
                  </template>
                </v-data-table>
              </td>
            </template>
          </v-data-table>
        </td>
      </template>
    </v-data-table>
  </v-container>
</template>

<script>
import { sum } from "lodash";
import jsonData from "./Untitled-2.json";

export default {
  data() {
    return {
      search: "",
      importSelected: [],
      office_id: 1,
      expanded: [],
      selected: [],
      providerHeaders: [
        { text: "Provider", value: "provider", width: "100px" },
        { text: "L|A", value: "", width: "100px", align: "center" },
        { text: "Sum", value: "sum", width: "100px", align: "center" },
        {
          text: "NEW",
          value: "hmnew",
          width: "100px",
          class: "new",
          cellClass: "new fz17",
          align: "center",
        },
        {
          text: "ReNEW",
          value: "hmrenew",
          width: "100px",
          class: "renew",
          cellClass: "renew fz17",
          align: "center",
        },
        {
          text: "CallBAck",
          value: "hmcb",
          width: "100px",
          class: "callback",
          cellClass: "callback fz17",
          align: "center",
        },
        {
          text: "Deposit",
          value: "hmdp",
          width: "100px",
          class: "deposit",
          cellClass: "deposit fz17",
          align: "center",
        },
        {
          text: "Pending",
          value: "hmpnd",
          width: "100px",
          class: "pending",
          cellClass: "pending fz17",
          align: "center",
        },
        {
          text: "Potential",
          value: "hmpot",
          width: "100px",
          class: "potential",
          cellClass: "potential fz17",
          align: "center",
        },
        {
          text: "NoAnswer",
          value: "hmnoans",
          width: "100px",
          class: "noans",
          cellClass: "noans fz17",
          align: "center",
        },
        {
          text: "NotInterest",
          value: "hmnointerest",
          width: "100px",
          class: "nointerest",
          cellClass: "nointerest fz17",
          align: "center",
        },
        {
          text: "Кол-во",
          value: "hm",
          width: "100px",
          align: "center",
        },
      ],
      dateHeaders: [
        {
          text: "Date",
          value: "date",
          width: "100px",
        },
        { text: "L|A", value: "", width: "100px", align: "center" },
        { text: "Sum", value: "sum", width: "100px", align: "center" },
        {
          text: "NEW",
          value: "hmnew",
          width: "100px",
          class: "new",
          cellClass: "new fz17",
          align: "center",
        },
        {
          text: "ReNEW",
          value: "hmrenew",
          width: "100px",
          class: "renew",
          cellClass: "renew fz17",
          align: "center",
        },
        {
          text: "CB",
          value: "hmcb",
          width: "100px",
          class: "callback",
          cellClass: "callback fz17",
          align: "center",
        },
        {
          text: "DP",
          value: "hmdp",
          width: "100px",
          class: "deposit",
          cellClass: "deposit fz17",
          align: "center",
        },
        {
          text: "PND",
          value: "hmpnd",
          width: "100px",
          class: "pending",
          cellClass: "pending fz17",
          align: "center",
        },
        {
          text: "POT",
          value: "hmpot",
          width: "100px",
          class: "potential",
          cellClass: "potential fz17",
          align: "center",
        },
        {
          text: "NoAnswer",
          value: "hmnoans",
          width: "100px",
          class: "noans",
          cellClass: "noans fz17",
          align: "center",
        },
        {
          text: "NotInterest",
          value: "hmnointerest",
          width: "100px",
          class: "nointerest",
          cellClass: "nointerest fz17",
          align: "center",
        },
        {
          text: "HM",
          value: "hm",
          width: "100px",

          align: "center",
        },
      ],
      geoHeaders: [
        { text: "", value: "", width: "1px" },
        { text: "GEO", value: "geo", width: "100px" },
        { text: "Sum", value: "sum", width: "100px", align: "center" },
        { text: "L|A", value: "cp", width: "100px", align: "center" },
        {
          text: "NEW",
          value: "hmnew",
          width: "100px",
          class: "new",
          cellClass: "new fz17",
          align: "center",
        },
        {
          text: "ReNEW",
          value: "hmrenew",
          width: "100px",
          class: "renew",
          cellClass: "renew fz17",
          align: "center",
        },
        {
          text: "CB",
          value: "hmcb",
          width: "100px",
          class: "callback",
          cellClass: "callback fz17",
          align: "center",
        },
        {
          text: "DP",
          value: "hmdp",
          width: "100px",
          class: "deposit",
          cellClass: "deposit fz17",
          align: "center",
        },
        {
          text: "PND",
          value: "hmpnd",
          width: "100px",
          class: "pending",
          cellClass: "pending fz17",
          align: "center",
        },
        {
          text: "POT",
          value: "hmpot",
          width: "100px",
          class: "potential",
          cellClass: "potential fz17",
          align: "center",
        },
        {
          text: "NoAnswer",
          value: "hmnoans",
          width: "100px",
          class: "noans",
          cellClass: "noans fz17",
          align: "center",
        },
        {
          text: "NotInterest",
          value: "hmnointerest",
          width: "100px",
          class: "nointerest",
          cellClass: "nointerest fz17",
          align: "center",
        },
        {
          text: "HM",
          value: "hm",
          width: "100px",

          align: "center",
        },
      ],
      providerSummaries: [],
    };
  },
  methods: {
    getproviderSummaries() {
      const data = jsonData;
      const providers = {};

      data.forEach((row) => {
        const hmData = JSON.parse(row.hm_json).find(
          (hm) => hm.office_id === this.office_id
        );
        if (!hmData) return;

        if (!providers[row.provider]) {
          providers[row.provider] = {
            id: row.provider_id, // Add a unique identifier
            provider: row.provider,
            hmnew: 0,
            hmcb: 0,
            hmdp: 0,
            hmpnd: 0,
            hmpot: 0,
            hm: 0,
            sum: 0,
            dates: {},
          };
        }

        providers[row.provider].hmnew += parseInt(hmData.hmnew);
        providers[row.provider].hmcb += parseInt(hmData.hmcb);
        providers[row.provider].hmdp += parseInt(hmData.hmdp);
        providers[row.provider].hmpnd += parseInt(hmData.hmpnd);
        providers[row.provider].hmpot += parseInt(hmData.hmpot);
        providers[row.provider].hm += parseInt(hmData.hm);
        providers[row.provider].sum += parseInt(row.sum);

        if (!providers[row.provider].dates[row.date]) {
          providers[row.provider].dates[row.date] = {
            date: row.date,
            hmnew: 0,
            hmcb: 0,
            hmdp: 0,
            hmpnd: 0,
            hmpot: 0,
            hm: 0,
            sum: 0,
            geo: [],
          };
        }

        providers[row.provider].dates[row.date].hmnew += parseInt(hmData.hmnew);
        providers[row.provider].dates[row.date].hmcb += parseInt(hmData.hmcb);
        providers[row.provider].dates[row.date].hmdp += parseInt(hmData.hmdp);
        providers[row.provider].dates[row.date].hmpnd += parseInt(hmData.hmpnd);
        providers[row.provider].dates[row.date].hmpot += parseInt(hmData.hmpot);
        providers[row.provider].dates[row.date].hm += parseInt(hmData.hm);
        providers[row.provider].dates[row.date].sum += parseInt(row.sum);

        providers[row.provider].dates[row.date].geo.push({
          geo: row.geo,
          cp: row.cp,
          provider_id: row.provider_id,
          hmnew: parseInt(hmData.hmnew),
          hmcb: parseInt(hmData.hmcb),
          hmdp: parseInt(hmData.hmdp),
          hmpnd: parseInt(hmData.hmpnd),
          hmpot: parseInt(hmData.hmpot),
          hm: parseInt(hmData.hm),
          sum: parseInt(row.sum),
          id: row.id,
        });
      });

      this.providerSummaries = Object.values(providers).map((provider) => {
        provider.dates = Object.values(provider.dates);
        return provider;
      });
    },
    toggleExpand(item) {
      console.log("Toggling expand for item:", item);
      const itemId = item.id; // Use the unique identifier
      if (this.expanded.includes(itemId)) {
        this.expanded = this.expanded.filter((id) => id !== itemId);
      } else {
        this.expanded = [itemId];
      }
      console.log("Expanded state:", this.expanded);
    },
  },
  created() {
    this.getproviderSummaries();
  },
};
</script>

<style>
.common-table .v-data-table-header {
  background-color: white;
}

.common-column {
  width: 100px;
  text-align: center;
}

.v-data-table__wrapper {
  overflow-x: hidden;
}

.common-table .v-data-table-header th,
.common-table td {
  width: 100px;
  max-width: 100px;
}

.common-table td {
  white-space: nowrap;
}
.v-data-table > .v-data-table__wrapper > table > tbody > tr > td {
  padding: 0;
}
/*.common-table .v-data-table-header {
   display: none;
}
#provTable.common-table
  > .v-data-table__wrapper
  > table
  > .v-data-table-header {
  display: contents;
}*/
.new,
.renew,
.callback,
.deposit,
.pending,
.potential .nointerest,
.noans {
  font-size: 1.2rem;
  font-weight: bold;
}
.new,
.theme--light.v-data-table.v-data-table--fixed-header thead th.new {
  background: #dde4e4ff !important;
}
.renew,
.theme--light.v-data-table.v-data-table--fixed-header thead th.renew {
  background: #c3f3ff !important;
}
.callback,
.theme--light.v-data-table.v-data-table--fixed-header thead th.callback {
  background: #1d91f0 !important;
}
.deposit,
.theme--light.v-data-table.v-data-table--fixed-header thead th.deposit {
  background: #21cb7bff !important;
}
.pending,
.theme--light.v-data-table.v-data-table--fixed-header thead th.pending {
  background: #a3adb7ff !important;
}
.potential,
.theme--light.v-data-table.v-data-table--fixed-header thead th.potential {
  background: #7fd74e !important;
}
.nointerest,
.theme--light.v-data-table.v-data-table--fixed-header thead th.nointerest {
  background: #a544d2 !important;
}
.noans,
.theme--light.v-data-table.v-data-table--fixed-header thead th.noans {
  background: #efa123 !important;
}
.text {
  font-size: unset;
  padding: 1rem;
  font-size: 0.75rem;
  font-weight: bold;
}
.fz17 {
  font-size: 17px !important;
  font-weight: bold;
  /* width: 182px; */
}
.v-data-table__expanded tr > td:nth-child(1) {
  background: linear-gradient(to left, rgb(194, 194, 194) 5%, transparent 0);
  width: min-content;
}
</style>
