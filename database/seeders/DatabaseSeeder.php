<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        $this->insertBinancePairs();

    }

    public function insertBinancePairs()
    {

        $pairs = [
            'SHIB/USDT',
            'AAVE/USDT',
            'ADA/USDT',
            'ALGO/USDT',
            'ATOM/USDT',
            'AVAX/USDT',
            'AXS/USDT',
            'BCH/USDT',
            'BNB/USDT',
            'BTC/USDT',
            'BTT/USDT',
            'CHZ/USDT',
            'COMP/USDT',
            'DASH/USDT',
            'DOGE/USDT',
            'DOT/USDT',
            'EGLD/USDT',
            'ENJ/USDT',
            'EOS/USDT',
            'ETC/USDT',
            'ETH/USDT',
            'FIL/USDT',
            'GRT/USDT',
            'HBAR/USDT',
            'HNT/USDT',
            'ICP/USDT',
            'KSM/USDT',
            'LINK/USDT',
            'LTC/USDT',
            'LUNA/USDT',
            'MANA/USDT',
            'MATIC/USDT',
            'MKR/USDT',
            'NEO/USDT',
            'SNX/USDT',
            'SOL/USDT',
            'SUSHI/USDT',
            'THETA/USDT',
            'TRX/USDT',
            'UNI/USDT',
            'VET/USDT',
            'WAVES/USDT',
            'XEM/USDT',
            'XLM/USDT',
            'XMR/USDT',
            'XRP/USDT',
            'XTZ/USDT',
            'ZEC/USDT',
            '1INCH/USDT',
            'AKRO/USDT',
            'ALICE/USDT',
            'ALPHA/USDT',
            'ANKR/USDT',
            'BAKE/USDT',
            'BAL/USDT',
            'BAND/USDT',
            'BAT/USDT',
            'BEL/USDT',
            'BLZ/USDT',
            'BTS/USDT',
            'BZRX/USDT',
            'CELR/USDT',
            'CHR/USDT',
            'COTI/USDT',
            'CRV/USDT',
            'CTK/USDT',
            'CVC/USDT',
            'DEFI/USDT',
            'DENT/USDT',
            'DGB/USDT',
            'DODO/USDT',
            'FLM/USDT',
            'FTM/USDT',
            'GTC/USDT',
            'HOT/USDT',
            'ICX/USDT',
            'IOST/USDT',
            'IOTA/USDT',
            'KAVA/USDT',
            'KEEP/USDT',
            'KNC/USDT',
            'LINA/USDT',
            'LIT/USDT',
            'LRC/USDT',
            'MTL/USDT',
            'NEAR/USDT',
            'NKN/USDT',
            'OCEAN/USDT',
            'OGN/USDT',
            'OMG/USDT',
            'ONE/USDT',
            'ONT/USDT',
            'QTUM/USDT',
            'REEF/USDT',
            'REN/USDT',
            'RLC/USDT',
            'RSR/USDT',
            'RUNE/USDT',
            'RVN/USDT',
            'SAND/USDT',
            'SC/USDT',
            'SFP/USDT',
            'SKL/USDT',
            'SRM/USDT',
            'STMX/USDT',
            'STORJ/USDT',
            'SXP/USDT',
            'THETA/USDT',
            'TLM/USDT',
            'TOMO/USDT',
            'TRB/USDT',
            'TRX/USDT',
            'UNFI/USDT',
            'UNI/USDT',
            'VET/USDT',
            'WAVES/USDT',
            'XEM/USDT',
            'XLM/USDT',
            'XMR/USDT',
            'XRP/USDT',
            'XTZ/USDT',
            'YFII/USDT',
            'YFI/USDT',
            'ZEC/USDT',
            'ZEN/USDT',
            'ZIL/USDT',
            'ZRX/USDT'
        ];

        foreach ($pairs as $pair) {

            \Illuminate\Support\Facades\DB::table('binance_pairs')->insertOrIgnore([
                'pair' => $pair
            ]);

        }

    }
}
