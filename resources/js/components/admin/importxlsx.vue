<template>
  <v-container>
    <!-- Первый уровень: Provider -->
    <!-- :expanded.sync="expanded"
    single-expand
      @click:row="toggleExpand"
    -->

    <v-data-table
      :headers="providerHeaders"
      :items="providerSummaries"
      item-value="provider"
      id="eventTable"
      show-expand
      single-expand
      :expanded.sync="expanded"
      class="elevation-1 common-table"
    >
      <template v-slot:expanded-item="{ item }">
        <!-- Второй уровень: Даты -->
        <td colspan="6">
          <v-data-table
            :headers="dateHeaders"
            :items="item.dates"
            item-value="date"
            id="dateTable"
            show-expand
            single-expand
            hide-default-footer
            class="common-table"
          >
            <template v-slot:expanded-item="{ item: dateItem }">
              <!-- Третий уровень: Языки -->
              <td colspan="6">
                <v-data-table
                  :headers="langHeaders"
                  :items="dateItem.languages"
                  item-value="lang"
                  id="langTable"
                  hide-default-footer
                  class="common-table"
                >
                  <template v-slot:item="{ item: langItem }">
                    <tr>
                      <td></td>
                      <td class="text-start common-column">
                        {{ langItem.lang }}
                      </td>
                      <td class="text-start common-column">
                        {{ langItem.p1 }}
                      </td>
                      <td class="text-start common-column">
                        {{ langItem.p2 }}
                      </td>
                      <td class="text-start common-column">
                        {{ langItem.p3 }}
                      </td>
                      <td class="text-center common-column green">
                        {{ langItem.p4 }}
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
export default {
  data() {
    return {
      expanded: [],
      // Заголовки для событий, дат и языков
      providerHeaders: [
        { text: "Event", value: "provider", width: "150px" },
        { text: "NEW", value: "p1", width: "100px" },
        { text: "DEPOSIT", value: "p2", width: "100px" },
        { text: "PENDING", value: "p3", width: "100px" },
        {
          text: "RENEW",
          value: "p4",
          width: "100px",
          class: "green",
          cellClass: "green",
          align: "center",
        },
      ],
      dateHeaders: [
        { text: "Date", value: "date", width: "150px" },
        { text: "NEW", value: "p1", width: "100px" },
        { text: "DEPOSIT", value: "p2", width: "100px" },
        { text: "PENDING", value: "p3", width: "100px" },
        {
          text: "RENEW",
          value: "p4",
          width: "100px",
          class: "green",
          cellClass: "green",
          align: "center",
        },
      ],
      langHeaders: [
        { text: "", value: "lang" },
        { text: "Lang", value: "lang", width: "150px" },
        { text: "NEW", value: "p1", width: "100px" },
        { text: "DEPOSIT", value: "p2", width: "100px" },
        { text: "PENDING", value: "p3", width: "100px" },
        {
          text: "RENEW",
          value: "p4",
          width: "100px",
          class: "green",
          cellClass: "green",
          align: "center",
        },
      ],
      providerSummaries: [],
    };
  },
  methods: {
    getproviderSummaries() {
      // Пример данных
      const data = [
        {
          provider: "Provider1",
          date: "05.10.24",
          lang: "PL",
          p1: 144,
          p2: 21,
          p3: 5,
          p4: 0,
        },
        {
          provider: "Provider1",
          date: "06.10.24",
          lang: "CZ",
          p1: 15,
          p2: 22234,
          p3: 6,
          p4: 1,
        },
        {
          provider: "Provider1",
          date: "06.10.24",
          lang: "PL",
          p1: 0,
          p2: 2,
          p3: 4,
          p4: 128899,
        },
        {
          provider: "Provider 22",
          date: "06.10.24",
          lang: "PL",
          p1: 5,
          p2: 3,
          p3: 0,
          p4: 0,
        },
        {
          provider: "Provider 22",
          date: "06.10.24",
          lang: "CZ",
          p1: 50,
          p2: 4,
          p3: 1,
          p4: 9,
        },
        {
          provider: "Provider 22",
          date: "07.10.24",
          lang: "CZ",
          p1: 51,
          p2: 5,
          p3: 2,
          p4: 10,
        },
        {
          provider: "Provider 22",
          date: "08.10.24",
          lang: "CZ",
          p1: 52,
          p2: 6,
          p3: 3,
          p4: 11,
        },
        {
          provider: "Provider 33",
          date: "08.10.24",
          lang: "CZ",
          p1: 52,
          p2: 6,
          p3: 3,
          p4: 11,
        },
      ];

      const providers = {};

      data.forEach((row) => {
        if (!providers[row.provider]) {
          providers[row.provider] = {
            provider: row.provider,
            p1: 0,
            p2: 0,
            p3: 0,
            p4: 0,
            dates: {},
          };
        }

        // Суммируем параметры для событий
        providers[row.provider].p1 += row.p1;
        providers[row.provider].p2 += row.p2;
        providers[row.provider].p3 += row.p3;
        providers[row.provider].p4 += row.p4;

        // Суммируем параметры для дат внутри события
        if (!providers[row.provider].dates[row.date]) {
          providers[row.provider].dates[row.date] = {
            date: row.date,
            p1: 0,
            p2: 0,
            p3: 0,
            p4: 0,
            languages: [],
          };
        }
        providers[row.provider].dates[row.date].p1 += row.p1;
        providers[row.provider].dates[row.date].p2 += row.p2;
        providers[row.provider].dates[row.date].p3 += row.p3;
        providers[row.provider].dates[row.date].p4 += row.p4;

        // Добавляем языки с параметрами для каждой даты
        providers[row.provider].dates[row.date].languages.push({
          lang: row.lang,
          p1: row.p1,
          p2: row.p2,
          p3: row.p3,
          p4: row.p4,
        });
      });

      // Преобразуем объект событий в массив
      this.providerSummaries = Object.values(providers).map((provider) => {
        provider.dates = Object.values(provider.dates);
        return provider;
      });
    },
    toggleExpand(item) {
      const index = this.expanded.indexOf(item);
      console.log(item, index);
      if (index > -1) {
        this.expanded.splice(index, 1);
      } else {
        this.expanded = [item];
      }
    },
    expandRow(item, event) {
      console.log(item, event);
      if (event.isExpanded) {
        var index = this.expanded.findIndex((i) => i === item);
        this.expanded.splice(index, 1);
      } else {
        this.expanded.push(item);
      }
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
  width: 100px; /* Фиксированная ширина */
  text-align: center;
}

.v-data-table__wrapper {
  overflow-x: hidden; /* Отключить горизонтальную прокрутку */
}

.common-table .v-data-table-header th,
.common-table td {
  width: 100px; /* Устанавливаем одинаковую ширину для всех таблиц */
  max-width: 100px;
}

.common-table td {
  white-space: nowrap;
}
.v-data-table > .v-data-table__wrapper > table > tbody > tr > td {
  padding: 0;
}
.common-table .v-data-table-header {
  display: none;
}
</style>
