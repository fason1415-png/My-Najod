import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:http/http.dart' as http;
import 'package:http/io_client.dart';
import 'package:speech_to_text/speech_to_text.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const NajotLinkVoiceApp());
}

class NajotLinkVoiceApp extends StatelessWidget {
  const NajotLinkVoiceApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'MY NajotLink Mobil',
      theme: ThemeData.dark(useMaterial3: true),
      home: const VoiceSosScreen(),
    );
  }
}

class _LocationPayload {
  final double latitude;
  final double longitude;
  final String locationText;

  const _LocationPayload({
    required this.latitude,
    required this.longitude,
    required this.locationText,
  });
}

class VoiceSosScreen extends StatefulWidget {
  const VoiceSosScreen({super.key});

  @override
  State<VoiceSosScreen> createState() => _VoiceSosScreenState();
}

class _VoiceSosScreenState extends State<VoiceSosScreen> {
  static const String _fallbackAddress =
      "Termiz shahri, I. Karimov ko'chasi 64-uy (Toshkent tibbiyot akademiyasi Termiz filiali)";
  static const double _fallbackLat = 37.224205;
  static const double _fallbackLng = 67.278317;

  final SpeechToText _speech = SpeechToText();
  final TextEditingController _apiCtrl = TextEditingController(
    text: 'https://87.237.235.107:4143/dashboard/06',
  );

  late final http.Client _client;

  bool _listening = false;
  bool _sending = false;
  bool _autoSubmitTriggered = false;

  String _status = "SOS tugmasini bosing va muammoni ayting";
  String _spoken = '';
  String _address = '-';
  String _localeInfo = '';
  String _result = '';
  double? _lat;
  double? _lng;

  @override
  void initState() {
    super.initState();
    _client = _buildHttpClient();
  }

  @override
  void dispose() {
    _apiCtrl.dispose();
    _client.close();
    super.dispose();
  }

  http.Client _buildHttpClient() {
    final io = HttpClient();
    io.badCertificateCallback = (cert, host, port) {
      return host == '87.237.235.107';
    };
    return IOClient(io);
  }

  Future<void> _onSosPressed() async {
    if (_sending) return;
    if (_listening) {
      await _stopAndSendNow();
      return;
    }
    await _startListening();
  }

  Future<void> _startListening() async {
    final ready = await _speech.initialize(
      onError: (e) {
        if (!mounted) return;
        setState(() {
          _listening = false;
          _status = 'Nutq xatoligi: ${e.errorMsg}';
        });
      },
      onStatus: (s) {
        if (!mounted) return;
        if ((s == 'done' || s == 'notListening') && _listening && !_sending && !_autoSubmitTriggered) {
          _autoSubmitTriggered = true;
          setState(() => _listening = false);
          Future<void>.microtask(_submitCurrentSpeech);
        }
      },
    );

    if (!ready) {
      setState(() => _status = "Mikrofon ruxsati yo'q yoki nutq xizmati topilmadi");
      return;
    }

    final localeId = await _resolveUzbekLocale();

    setState(() {
      _listening = true;
      _autoSubmitTriggered = false;
      _spoken = '';
      _result = '';
      _status = 'Tinglanmoqda... gapiring';
    });

    await _speech.listen(
      localeId: localeId,
      partialResults: true,
      listenFor: const Duration(seconds: 20),
      pauseFor: const Duration(seconds: 3),
      onResult: (res) {
        if (!mounted) return;
        setState(() {
          _spoken = res.recognizedWords.trim();
          _status = 'Ovoz matnga olinmoqda...';
        });
      },
    );
  }

  Future<String> _resolveUzbekLocale() async {
    String locale = 'uz_UZ';
    String info = "Nutq tili: O'zbekcha";

    try {
      final locales = await _speech.locales();
      String? foundUz;
      for (final l in locales) {
        if (l.localeId.toLowerCase().contains('uz')) {
          foundUz = l.localeId;
          break;
        }
      }
      if (foundUz != null) {
        locale = foundUz;
        info = "Nutq tili: $locale";
      } else {
        info = "Diqqat: telefonda O'zbek speech paketi topilmadi, natija aralash bo'lishi mumkin";
      }
    } catch (_) {
      info = "Nutq tili: O'zbekcha (majburiy)";
    }

    if (mounted) {
      setState(() => _localeInfo = info);
    }
    return locale;
  }

  Future<void> _stopAndSendNow() async {
    if (_sending) return;
    _autoSubmitTriggered = true;
    await _speech.stop();
    if (mounted) setState(() => _listening = false);
    await _submitCurrentSpeech();
  }

  Future<void> _submitCurrentSpeech() async {
    if (_sending) return;

    final text = _spoken.trim();
    if (text.isEmpty) {
      if (mounted) {
        setState(() => _status = "Matn aniqlanmadi. Iltimos, qayta gapiring.");
      }
      return;
    }

    if (mounted) {
      setState(() {
        _sending = true;
        _status = 'Lokatsiya olinmoqda...';
      });
    }

    try {
      final loc = await _resolveLocationPayload();
      _lat = loc.latitude;
      _lng = loc.longitude;
      _address = loc.locationText;

      if (mounted) {
        setState(() => _status = 'SOS serverga yuborilmoqda...');
      }

      final data = await _sendToServer(
        baseUrl: _apiCtrl.text.trim(),
        voiceText: text,
        latitude: loc.latitude,
        longitude: loc.longitude,
        locationText: loc.locationText,
      );

      if (mounted) {
        setState(() {
          _status = "Yuborildi. Yo'naltirish: ${data['alert']?['service'] ?? '-'}";
          _result = jsonEncode(data);
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() => _status = 'Xatolik: $e');
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  Future<_LocationPayload> _resolveLocationPayload() async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        return const _LocationPayload(
          latitude: _fallbackLat,
          longitude: _fallbackLng,
          locationText: "GPS o'chirilgan. Fallback manzil: $_fallbackAddress",
        );
      }

      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) {
        perm = await Geolocator.requestPermission();
      }
      if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
        return const _LocationPayload(
          latitude: _fallbackLat,
          longitude: _fallbackLng,
          locationText: "Lokatsiya ruxsati yo'q. Fallback manzil: $_fallbackAddress",
        );
      }

      final pos = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      final lat = double.parse(pos.latitude.toStringAsFixed(6));
      final lng = double.parse(pos.longitude.toStringAsFixed(6));
      return _LocationPayload(
        latitude: lat,
        longitude: lng,
        locationText: "Aniq joylashuv: $lat, $lng",
      );
    } catch (_) {
      return const _LocationPayload(
        latitude: _fallbackLat,
        longitude: _fallbackLng,
        locationText: "GPS olinmadi. Fallback manzil: $_fallbackAddress",
      );
    }
  }

  String _normalizedBaseUrl(String input) {
    final trimmed = input.trim();
    if (trimmed.endsWith('/')) {
      return trimmed.substring(0, trimmed.length - 1);
    }
    return trimmed;
  }

  Future<Map<String, dynamic>> _sendToServer({
    required String baseUrl,
    required String voiceText,
    required double latitude,
    required double longitude,
    required String locationText,
  }) async {
    final cleanBase = _normalizedBaseUrl(baseUrl);
    if (cleanBase.isEmpty) {
      throw Exception("API URL bo'sh");
    }

    final payload = jsonEncode({
      'user_id': 'USR-001',
      'device_id': 'DEV-001',
      'voice_text': voiceText,
      'latitude': latitude,
      'longitude': longitude,
      'location_text': locationText,
    });

    final endpoints = ['sos-create-v2.php', 'sos-create.php'];
    String? lastError;

    for (final endpoint in endpoints) {
      final uri = Uri.parse('$cleanBase/$endpoint');
      final response = await _client.post(
        uri,
        headers: {'Content-Type': 'application/json'},
        body: payload,
      );

      if (response.statusCode == 404) {
        lastError = '$endpoint topilmadi';
        continue;
      }

      if (response.body.trim().isEmpty) {
        lastError = "Server bo'sh javob qaytardi";
        continue;
      }

      Map<String, dynamic> parsed;
      try {
        parsed = jsonDecode(response.body) as Map<String, dynamic>;
      } catch (_) {
        lastError = "Server JSON bo'lmagan javob qaytardi (${response.statusCode})";
        continue;
      }

      if (response.statusCode >= 200 && response.statusCode < 300 && parsed['ok'] == true) {
        return parsed;
      }

      lastError = parsed['message']?.toString() ?? "Server xatoligi (${response.statusCode})";
    }

    throw Exception(lastError ?? "Yuborishda noma'lum xatolik");
  }

  @override
  Widget build(BuildContext context) {
    final buttonText = _sending
        ? 'Yuborilmoqda...'
        : (_listening ? "To'xtatish/Yuborish" : 'SOS');

    return Scaffold(
      appBar: AppBar(title: const Text('MY NajotLink Mobil')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: ListView(
          children: [
            TextField(
              controller: _apiCtrl,
              decoration: const InputDecoration(
                labelText: 'Server manzili (API)',
                hintText: 'https://87.237.235.107:4143/dashboard/06',
              ),
            ),
            const SizedBox(height: 12),
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: Colors.white24),
              ),
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Holat: $_status'),
                  if (_localeInfo.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(_localeInfo),
                  ],
                  const SizedBox(height: 8),
                  Text("Matn: ${_spoken.isEmpty ? '-' : _spoken}"),
                  const SizedBox(height: 8),
                  Text("GPS: ${_lat == null ? '-' : '${_lat!.toStringAsFixed(6)}, ${_lng!.toStringAsFixed(6)}'}"),
                  const SizedBox(height: 8),
                  Text('Manzil: $_address'),
                ],
              ),
            ),
            const SizedBox(height: 18),
            Center(
              child: GestureDetector(
                onTap: _onSosPressed,
                child: Container(
                  width: 220,
                  height: 220,
                  decoration: const BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: RadialGradient(
                      colors: [Color(0xFFFF9AB1), Color(0xFFFF4D6D), Color(0xFF7A0820)],
                    ),
                    boxShadow: [
                      BoxShadow(color: Color(0x66FF4D6D), blurRadius: 40, spreadRadius: 8),
                    ],
                  ),
                  child: Center(
                    child: Text(
                      buttonText,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 34, fontWeight: FontWeight.w800),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 16),
            if (_result.isNotEmpty)
              Container(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: Colors.white24),
                ),
                padding: const EdgeInsets.all(12),
                child: Text('Server javobi: $_result'),
              ),
          ],
        ),
      ),
    );
  }
}
